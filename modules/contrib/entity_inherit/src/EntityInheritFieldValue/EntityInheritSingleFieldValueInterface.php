<?php

namespace Drupal\entity_inherit\EntityInheritFieldValue;

/**
 * A single field value and its previous value.
 */
interface EntityInheritSingleFieldValueInterface {

  /**
   * Check if the previous value is different from the current value.
   *
   * @return bool
   *   TRUE if the previous value is different from the current value.
   */
  public function changed() : bool;

  /**
   * Get the field name.
   *
   * @return string
   *   The field name.
   */
  public function fieldName() : string;

  /**
   * The new value.
   *
   * @return array
   *   The new value.
   */
  public function newValue() : array;

  /**
   * The previous value.
   *
   * @return array
   *   The previous value.
   */
  public function previousValue() : array;

}
