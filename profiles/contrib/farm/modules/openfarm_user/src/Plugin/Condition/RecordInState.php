<?php

namespace Drupal\openfarm_user\Plugin\Condition;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic 'Record in state' condition.
 *
 * @Condition(
 *   id = "openfarm_record_state",
 *   deriver = "Drupal\openfarm_user\Plugin\Condition\RecordInStateDeriver"
 * )
 */
class RecordInState extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * Node type manager.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Check if the provided entity is new.
   *
   * @param int $id
   *   The entity id.
   *
   * @return bool
   *   TRUE if the provided entity is new.
   */
  protected function doEvaluate($id) {
    return $this->storage->load($id)->moderation_state->value === $this->getDerivativeId();
  }

}
