<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritSingleFieldValueInterface;

/**
 * An entity which can be new or existing, which can contain revisions.
 */
abstract class EntityInheritEntity extends EntityInheritEntityRevision implements EntityInheritUpdatableEntityInterface, EntityInheritEntitySingleInterface {

  /**
   * Check if a field should be applied.
   *
   * @param \Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritSingleFieldValueInterface $field_value
   *   A field value.
   *
   * @return bool
   *   TRUE if a field should be applied.
   */
  abstract public function applies(EntityInheritSingleFieldValueInterface $field_value) : bool;

  /**
   * {@inheritdoc}
   */
  public function presave() {
    $this->presaveAsChild();
    $this->presaveAsParent();
  }

  /**
   * Presave this enity in its role as a child.
   */
  public function presaveAsChild() {
    foreach ($this->getMergedParents()->preload()->toArray() as $entity) {
      foreach ($entity->fieldValues()->toArray() as $fieldvalue) {
        $this->updateField($fieldvalue);
      }
    }
  }

  /**
   * Presave this enity in its role as a parent.
   */
  abstract public function presaveAsParent();

  /**
   * Set a field value.
   *
   * @param string $field_name
   *   A field name.
   * @param array $value
   *   A field value.
   */
  public function set(string $field_name, array $value) {
    $drupal_entity = $this->getDrupalEntity();

    // See https://github.com/mglaman/phpstan-drupal/issues/159.
    // @phpstan-ignore-next-line
    $drupal_entity->set($field_name, $value);

    $this->drupalEntity = $drupal_entity;
  }

  /**
   * Update a field based on field values.
   *
   * @param \Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritSingleFieldValueInterface $field_value
   *   A field value.
   */
  public function updateField(EntityInheritSingleFieldValueInterface $field_value) {
    if ($this->applies($field_value)) {
      $this->set($field_value->fieldName(), $field_value->newValue());
    }
  }

}
