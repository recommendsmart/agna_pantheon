<?php

namespace Drupal\openfarm_holding\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\openfarm_holding\Event\OpenfarmHoldingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class OpenfarmHoldingService.
 *
 * @package Drupal\openfarm_holding\Service
 */
class OpenfarmHoldingService implements OpenfarmHoldingServiceInterface {

  /**
   * The count of nodes which will be processed at a time.
   */
  const LIMIT = 5;

  /**
   * Entity Type Manager service object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a OpenfarmHoldingService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EventDispatcherInterface $eventDispatcher, TimeInterface $time) {
    $this->entityTypeManager = $entityTypeManager;
    $this->eventDispatcher = $eventDispatcher;
    $this->time = $time;
  }

  /**
   * Processing for closing / opening scheduled nodes.
   *
   * @param string $operation
   *   The name of operation 'open/close'.
   *
   * @return bool
   *   TRUE if any node has been closed / opened, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function holdingsOperation($operation) {
    $result = FALSE;
    $operation_field_name = $operation == 'open' ? 'field_schedule_open' : 'field_schedule_close';
    $event_name = $operation == 'open' ? OpenfarmHoldingEvent:: HOLDING_OPEN : OpenfarmHoldingEvent:: HOLDING_CLOSE;

    // Select all nodes of the holding type that are enabled for scheduled
    // closing/opening and where close_on/open_on is less than or equal
    // to the current time.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->exists($operation_field_name)
      ->condition($operation_field_name, $this->time->getRequestTime(), '<=')
      ->condition('type', 'holding', '=')
      ->condition('status', NodeInterface::PUBLISHED)
      ->latestRevision()
      ->sort($operation_field_name)
      ->sort('nid')
      ->range(0, self::LIMIT);
    $nids = $query->execute();

    if (empty($nids)) {
      return $result;
    }

    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($nids);
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $node) {
      // Mark holding node as opened/closed.
      $node->set('field_is_open', ($operation == 'open'));
      // Unset open_on/close_on field's value.
      $node->set($operation_field_name, NULL);
      $node->save();

      // Trigger the HOLDING_CLOSE event so that modules can react after the
      // node is closed.
      $event = new OpenfarmHoldingEvent($node);
      $this->eventDispatcher->dispatch($event_name, $event);

      $result = TRUE;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function openHoldings() {
    $this->holdingsOperation('open');
  }

  /**
   * {@inheritdoc}
   */
  public function closeHoldings() {
    $this->holdingsOperation('close');
  }

  /**
   * {@inheritdoc}
   */
  public function getCountOfRecords($holding_id) {
    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery();
    $query->condition('type', 'record');
    $query->condition('status', NodeInterface::PUBLISHED);
    $query->condition('field_holding', $holding_id);
    $query->accessCheck(TRUE);
    $query->count();
    $result = $query->execute();

    return !empty($result) ? $result : '0';
  }

}
