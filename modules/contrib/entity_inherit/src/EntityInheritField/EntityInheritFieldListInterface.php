<?php

namespace Drupal\entity_inherit\EntityInheritField;

/**
 * Reprensents a Drupal field list.
 */
interface EntityInheritFieldListInterface extends \Countable {

  /**
   * Add a field to the array.
   *
   * @param \Drupal\entity_inherit\EntityInheritField\EntityInheritField $field
   *   A field to add.
   */
  public function add(EntityInheritField $field);

  /**
   * Get only invalid fields, no duplicates.
   *
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   Invalid fields.
   */
  public function invalidOnly(string $category) : EntityInheritFieldListInterface;

  /**
   * Get as array.
   *
   * @return array
   *   Array of fields.
   */
  public function toArray() : array;

  /**
   * Get as a text area.
   *
   * @return string
   *   The fields as a text area.
   */
  public function toTextArea() : string;

  /**
   * Get only valid fields, no duplicates.
   *
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   Valid fields.
   */
  public function validOnly(string $category) : EntityInheritFieldListInterface;

}
