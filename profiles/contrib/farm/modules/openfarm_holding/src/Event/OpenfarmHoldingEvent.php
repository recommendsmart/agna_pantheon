<?php

namespace Drupal\openfarm_holding\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class OpenfarmHoldingEvent.
 *
 * @package Drupal\openfarm_holding\Event
 */
class OpenfarmHoldingEvent extends Event {

  /**
   * The event triggered after opening a holding node via cron.
   *
   * @Event
   *
   * @var string
   */
  const CHALLENGE_OPEN = 'openfarm_holding.open';

  /**
   * The event triggered after closing a holding node via cron.
   *
   * @Event
   *
   * @var string
   */
  const CHALLENGE_CLOSE = 'openfarm_holding.close';

  /**
   * Node object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public $entity;

  /**
   * OpenfarmHoldingEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node object that caused the event to fire.
   */
  public function __construct(EntityInterface $node) {
    $this->entity = $node;
  }

  /**
   * Gets node object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The node object that caused the event to fire.
   */
  public function getEntity() {
    return $this->entity;
  }

}
