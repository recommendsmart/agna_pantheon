<?php

namespace Drupal\commerce_product_bundles;

use Drupal\commerce\CommerceEntityViewsData;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity\BundleFieldDefinition;

/**
 * Class CommerceBundleEntityViewsData
 *
 * @package Drupal\commerce_product_bundles
 */
class CommerceBundleEntityViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $this->tableMapping = $this->storage->getTableMapping();
    $entity_type_id = $this->entityType->id();
    // Workaround for core issue #3004300.
    if ($this->entityType->isRevisionable()) {
      $revision_table = $this->tableMapping->getRevisionTable();
      $data[$revision_table]['table']['entity revision'] = TRUE;
    }
    // Add missing reverse relationships. Workaround for core issue #2706431.
    $base_fields = $this->getEntityFieldManager()->getBaseFieldDefinitions($entity_type_id);
    $entity_reference_fields = array_filter($base_fields, function (BaseFieldDefinition $field) {
      return $field->getType() == 'entity_reference';
    });
    if (in_array($entity_type_id, ['commerce_order', 'commerce_product_bundles'])) {
      // Product bundle variations and order items have reference fields pointing
      // to the parent entity, no need for a reverse relationship.
      unset($entity_reference_fields['bundle_variations']);
      unset($entity_reference_fields['order_items']);
    }
    $this->addReverseRelationships($data, $entity_reference_fields);
    // Add views integration for bundle plugin fields.
    // Workaround for core issue #2898635.
    if ($this->entityType->hasHandlerClass('bundle_plugin')) {
      $bundles = $this->getEntityTypeBundleInfo()->getBundleInfo($entity_type_id);
      foreach (array_keys($bundles) as $bundle) {
        $field_definitions = $this->getEntityFieldManager()->getFieldDefinitions($entity_type_id, $bundle);
        foreach ($field_definitions as $field_definition) {
          if ($field_definition instanceof BundleFieldDefinition) {
            $this->addBundleFieldData($data, $field_definition);
          }
        }
      }
    }
    // Use custom bundle handlers which know how to handle non-config bundles.
    // Workaround for core issue #3056998.
    if ($bundle_key = $this->entityType->getKey('bundle')) {
      $base_table = $this->getViewsTableForEntityType($this->entityType);
      $data[$base_table][$bundle_key]['field']['id'] = 'commerce_entity_bundle';
      $data[$base_table][$bundle_key]['filter']['id'] = 'commerce_entity_bundle';
    }

    return $data;
  }

}
