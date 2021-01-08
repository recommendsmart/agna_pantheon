<?php

namespace Drupal\entity_inherit\EntityInheritStorage;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface;

/**
 * Storage.
 */
class EntityInheritStorage implements EntityInheritStorageInterface {

  /**
   * The app singleton.
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The app singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->app = $app;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildrenOf(string $type, string $id) : EntityInheritExistingMultipleEntitiesInterface {
    $drupal_nodes = [];

    foreach (array_keys($this->app->getParentEntityFields()->validOnly('parent')->toArray()) as $field) {
      $drupal_nodes = array_merge($drupal_nodes,
        $this->app->getEntityTypeManager()
          ->getListBuilder($type)
          ->getStorage()
          ->loadByProperties([
            $field => $id,
          ]));
    }

    return $this->app->getEntityFactory()->newCollection($drupal_nodes);
  }

}
