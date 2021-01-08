<?php

namespace Drupal\entity_inherit\EntityInheritFieldValue;

use Drupal\entity_inherit\EntityInherit;

/**
 * A field value and its previous value.
 */
class EntityInheritFieldValue implements EntityInheritFieldValueInterface, EntityInheritSingleFieldValueInterface {

  /**
   * The app singleton.
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
   * The field value.
   *
   * @var array
   */
  protected $value;

  /**
   * The origianl value.
   *
   * @var array
   */
  protected $previous;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The app singleton.
   * @param string $field_name
   *   The field name.
   * @param array $value
   *   The new value.
   * @param array $previous
   *   The original value.
   */
  public function __construct(EntityInherit $app, string $field_name, array $value, array $previous) {
    $this->app = $app;
    $this->fieldName = $field_name;
    $this->value = $value;
    $this->previous = $previous;
  }

  /**
   * {@inheritdoc}
   */
  public function changed() : bool {
    return $this->newValue() != $this->previousValue();
  }

  /**
   * {@inheritdoc}
   */
  public function newValue() : array {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function previousValue() : array {
    return $this->previous;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldName() : string {
    return $this->fieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    return [
      $this->fieldName() => $this,
    ];
  }

}
