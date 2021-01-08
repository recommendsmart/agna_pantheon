<?php

namespace Drupal\entity_inherit\EntityInheritField;

/**
 * Reprensents a Drupal field list.
 */
class EntityInheritFieldList implements EntityInheritFieldListInterface {

  /**
   * The internal list of field objects.
   *
   * @var array
   */
  protected $array;

  /**
   * Constructor.
   *
   * @param array $array
   *   An array of field objects.
   */
  public function __construct(array $array = []) {
    $this->array = $array;
  }

  /**
   * {@inheritdoc}
   */
  public function add(EntityInheritField $field) {
    $this->array[$field->__toString()] = $field;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->array);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidOnly(string $category) : EntityInheritFieldListInterface {
    $invalid = new EntityInheritFieldList();

    foreach ($this->array as $candidate) {
      if (!$candidate->valid($category)) {
        $invalid->add($candidate);
      }
    }

    return $invalid;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    return $this->array;
  }

  /**
   * {@inheritdoc}
   */
  public function toTextArea() : string {
    return implode(PHP_EOL, $this->array);
  }

  /**
   * {@inheritdoc}
   */
  public function validOnly(string $category) : EntityInheritFieldListInterface {
    $valid = new EntityInheritFieldList();

    foreach ($this->array as $candidate) {
      if ($candidate->valid($category)) {
        $valid->add($candidate);
      }
    }

    return $valid;
  }

}
