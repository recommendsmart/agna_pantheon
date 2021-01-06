<?php

namespace Drupal\openfarm_statistics;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\statistics\NodeStatisticsDatabaseStorage;

/**
 * LazyBuilder object.
 */
class OpenfarmStatisticsLazyBuilder {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Node statistics service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $nodeStatistics;

  /**
   * LazyBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $node_statistics_database_storage
   *   Node statistics service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Token $token, NodeStatisticsDatabaseStorage $node_statistics_database_storage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->token = $token;
    $this->nodeStatistics = $node_statistics_database_storage;
  }

  /**
   * Build element that return record count.
   *
   * @return array
   *   Renderable array.
   */
  public function getRecords() {
    return [
      '#markup' => $this->token->replace('[openfarm:records-count]'),
    ];
  }

  /**
   * Build element that return user records.
   *
   * @param int $id
   *   User id.
   *
   * @return array
   *   Renderable array.
   */
  public function getUserRecords($id) {
    $user = $this->entityTypeManager->getStorage('user')->load($id);
    return [
      '#markup' => $this->token->replace('[openfarm:user-records-count]', ['user' => $user]),
    ];
  }

  /**
   * Build element that return user votes.
   *
   * @param int $id
   *   User id.
   *
   * @return array
   *   Renderable array.
   */
  public function getUserVotes($id) {
    $user = $this->entityTypeManager->getStorage('user')->load($id);
    return [
      '#markup' => $this->token->replace('[openfarm:user-votes-count]', ['user' => $user]),
    ];
  }

  /**
   * Build element that return user comments count.
   *
   * @param int $id
   *   User id.
   *
   * @return array
   *   Renderable array.
   */
  public function getUserComments($id) {
    $user = $this->entityTypeManager->getStorage('user')->load($id);
    return [
      '#markup' => $this->token->replace('[openfarm:user-comments-count]', ['user' => $user]),
    ];
  }

  /**
   * Build element that return members count.
   *
   * @return array
   *   Renderable array.
   */
  public function getMembers() {
    return [
      '#markup' => $this->token->replace('[openfarm:members-count]'),
    ];
  }

  /**
   * Build element that return comments count.
   *
   * @param int $id
   *   Node id.
   *
   * @return array
   *   Renderable array.
   */
  public function getComments($id = NULL) {
    $node = $id ? $this->entityTypeManager->getStorage('node')->load($id) : NULL;
    $markup = $node
      ? $this->token->replace('[node:comment-count]', ['node' => $node])
      : $this->token->replace('[openfarm:comments-count]');
    return [
      '#markup' => $markup,
    ];
  }

  /**
   * Get the node count of the views.
   *
   * @param int $id
   *   The node id.
   *
   * @return array
   *   Renderable array.
   */
  public function getViews($id) {
    $node = $this->entityTypeManager->getStorage('node')->load($id);
    $statistics_result = $this->nodeStatistics->fetchView($node->id());
    return [
      // In case the node created for the first time result will be false,
      // so need to check it.
      '#markup' => $statistics_result ? $statistics_result->getTotalCount() : 0,
    ];
  }

  /**
   * Build element that return votes count.
   *
   * @param int $id
   *   Node id.
   *
   * @return array
   *   Renderable array.
   */
  public function getVotes($id = NULL) {
    $node = $id ? $this->entityTypeManager->getStorage('node')->load($id) : NULL;
    $markup = $node
      ? $this->token->replace('[openfarm:node-votes-count]', ['node' => $node])
      : $this->token->replace('[openfarm:votes-count]');
    return [
      '#markup' => $markup,
    ];
  }

  /**
   * Build element that return Holding records count.
   *
   * @param int $id
   *   Node id.
   *
   * @return array
   *   Renderable array.
   */
  public function getHoldingRecords($id) {
    $markup = $this->token->replace('[openfarm:holding-records-count]', ['node_id' => $id]);

    return [
      '#markup' => $markup,
    ];
  }

}
