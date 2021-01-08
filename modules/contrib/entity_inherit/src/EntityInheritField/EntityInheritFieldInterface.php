<?php

namespace Drupal\entity_inherit\EntityInheritField;

/**
 * Reprensents a Drupal field.
 */
interface EntityInheritFieldInterface {

  /**
   * Stringify.
   *
   * @return string
   *   The field name.
   */
  public function __toString();

  /**
   * Whether or not this field is valid.
   *
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return bool
   *   Whether or not this field is valid.
   */
  public function valid(string $category) : bool;

}
