<?php

namespace Drupal\entity_inherit\Plugin\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginBase;

/**
 * Make sure only entity reference fields can be used to store parents.
 *
 * @EntityInheritPluginAnnotation(
 *   id = "entity_inherit_filter_reference_fields",
 *   description = @Translation("Make sure only entity reference fields can be used to store parents."),
 *   weight = -100,
 * )
 */
class EntityInheritFilterReferenceFields extends EntityInheritPluginBase {

  /**
   * {@inheritdoc}
   */
  public function filterFields(array &$field_names, array $original, string $category, EntityInherit $app) {
    if ($category == 'parent') {
      foreach ($field_names as $field_name => $field) {
        if ($field['type'] != 'entity_reference') {
          unset($field_names[$field_name]);
        }
      }
    }
  }

}
