<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'bundle_ref_var_field' field type.
 *
 * @FieldType(
 *   id = "bundle_ref_var_field",
 *   label = @Translation("Commerce product bundle reference variants field"),
 *   description = @Translation("Field containing ref quantity and variationns for line item."),
 *   category = @Translation("Commerce Product Bundles"),
 *   default_widget = "bundle_ref_var_field_default",
 *   default_formatter = "bundle_ref_var_field_formatter"
 * )
 */
class BundleRefVariationField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'description' => 'The base table for Commerce product bundle field type.',
      'columns' => [
        'product_var_id' => [
          'description' => 'The ID of the target entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'quantity' => [
          'description' => 'Product variation quantity',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 1,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['product_var_id'] = DataDefinition::create('integer')
      ->setLabel('Product')
      ->setSetting('unsigned', TRUE);

    $properties['quantity'] = DataDefinition::create('integer')
      ->setLabel('Quantity')
      ->setSetting('unsigned', TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->values = [];
    if (!isset($values)) {
      return;
    }

    $product_var_id = NULL;
    if(isset($values['product_var_id'])){
      $product_var_id = $values['product_var_id'];
    }
    $this->values['product_var_id'] = $product_var_id;

    $quantity = NULL;
    if(isset($values['quantity'])){
      $quantity = $values['quantity'];
    }
    $this->values['quantity'] = $quantity;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    // A map item has no main property.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() { }

}
