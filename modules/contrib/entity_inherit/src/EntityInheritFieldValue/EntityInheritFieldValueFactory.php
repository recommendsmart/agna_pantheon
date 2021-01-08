<?php

namespace Drupal\entity_inherit\EntityInheritFieldValue;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\Utilities\FriendTrait;

/**
 * A factory to build entities. Instantiate through EntityEnherit.
 */
class EntityInheritFieldValueFactory {

  use FriendTrait;

  /**
   * The EntityInherit singleton (service).
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The application singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->friendAccess([EntityInherit::class]);
    $this->app = $app;
  }

  /**
   * Get a new collection.
   *
   * @return \Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueCollectionInterface
   *   A new collection.
   */
  public function newCollection() : EntityInheritFieldValueCollectionInterface {
    return new EntityInheritFieldValueCollection($this->app);
  }

  /**
   * Get a new field value.
   *
   * @param string $field_name
   *   The field name.
   * @param array $value
   *   The new value.
   * @param array $previous
   *   The previous value.
   *
   * @return \Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueInterface
   *   A new field value object.
   */
  public function newFieldValue(string $field_name, array $value, array $previous) : EntityInheritFieldValueInterface {
    return new EntityInheritFieldValue($this->app, $field_name, $value, $previous);
  }

}
