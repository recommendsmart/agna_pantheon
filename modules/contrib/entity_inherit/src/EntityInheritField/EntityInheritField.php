<?php

namespace Drupal\entity_inherit\EntityInheritField;

use Drupal\entity_inherit\EntityInherit;

/**
 * Reprensents a Drupal field.
 */
class EntityInheritField implements EntityInheritFieldInterface {

  /**
   * The EntityInherit singleton (service).
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The global app singleton.
   * @param string $field_name
   *   A field name.
   */
  public function __construct(EntityInherit $app, string $field_name) {
    $this->app = $app;
    $this->fieldName = trim($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->fieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function validInheritable() : bool {
    return $this->app->validFieldName($this->fieldName, 'inheritable');
  }

  /**
   * {@inheritdoc}
   */
  public function valid(string $category) : bool {
    return $this->app->validFieldName($this->fieldName, $category);
  }

}
