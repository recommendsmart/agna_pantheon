<?php

namespace Drupal\entity_inherit\EntityInheritField;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\Utilities\FriendTrait;

/**
 * A factory to build fields. Instantiate through EntityEnherit.
 */
class EntityInheritFieldFactory {

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
   * Create a list of fields from an array.
   *
   * @param array $array
   *   An array of field names.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   A field list.
   */
  public function fromArray(array $array) : EntityInheritFieldListInterface {
    $return = new EntityInheritFieldList();
    foreach ($array as $field_name) {
      if (trim($field_name)) {
        $return->add($this->fromFieldName($field_name));
      }
    }
    return $return;
  }

  /**
   * Create a field from a field name.
   *
   * @param string $field_name
   *   A field name.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritField
   *   A field list.
   */
  public function fromFieldName(string $field_name) : EntityInheritField {
    return new EntityInheritField($this->app, $field_name);
  }

}
