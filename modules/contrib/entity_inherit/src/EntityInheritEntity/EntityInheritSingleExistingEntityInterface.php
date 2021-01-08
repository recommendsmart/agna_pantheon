<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

/**
 * A single existing entity.
 */
interface EntityInheritSingleExistingEntityInterface extends EntityInheritExistingEntityInterface {

  /**
   * Process this entity based on a changed parent.
   *
   * @param array $parent
   *   A parent information, as a queueable item.
   */
  public function process(array $parent);

  /**
   * Get a unique string which identifies this object.
   *
   * @return string
   *   A unique string.
   */
  public function toStorageId() : string;

}
