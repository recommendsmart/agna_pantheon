<?php

namespace Drupal\entity_inherit\Plugin\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginBase;

/**
 * Make sure parent fields are not inheritable.
 *
 * @EntityInheritPluginAnnotation(
 *   id = "entity_inherit_filter_inheritable_fields",
 *   description = @Translation("Make sure parent fields are not inheritable."),
 *   weight = -100,
 * )
 */
class EntityInheritFilterInheritableFields extends EntityInheritPluginBase {

  /**
   * {@inheritdoc}
   */
  public function filterFields(array &$field_names, array $original, string $category, EntityInherit $app) {
    if ($category == 'inheritable') {
      foreach ($app->getParentEntityFields()->validOnly('parent')->toArray() as $field) {
        unset($field_names[$field->__toString()]);
      }
    }
  }

}
