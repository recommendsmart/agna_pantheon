<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;

/**
 * Plugin implementation of the 'product_bundle_field' field type.
 *
 * @FieldType(
 *   id = "product_bundle_field",
 *   label = @Translation("Commerce product bundle field"),
 *   description = @Translation("Field containing areferenced product variations and quantity"),
 *   category = @Translation("Commerce Product Bundles"),
 *   default_widget = "product_bundle_field_default",
 *   default_formatter = "product_bundle_field_formatter"
 * )
 */
class CommerceBundleField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'description' => 'The base table for Commerce product bundle field type.',
      'columns' => [
        'variation_ids' => [
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
          'description' => 'Variation ids.',
        ],
        'product_id' => [
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
    $properties['variation_ids'] = MapDataDefinition::create()
      ->setRequired(TRUE)
      ->setLabel(t('Product variation id'));

    $properties['product_id'] = DataDefinition::create('integer')
      ->setLabel('Product')
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE);

    $properties['quantity'] = DataDefinition::create('integer')
      ->setLabel('Quantity')
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    // Remove the 'AllowedValuesConstraint' validation constraint because entity
    // reference fields already use the 'ValidReference' constraint.
    foreach ($constraints as $key => $constraint) {
      if ($constraint instanceof AllowedValuesConstraint) {
        unset($constraints[$key]);
      }
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->values = [];
    if (!isset($values)) {
      return;
    }

    $product_id = NULL;
    if(isset($values['product_id'])){
      $product_id = $values['product_id'];
    }
    $this->values['product_id'] = $product_id;

    // Get stores value for serialization.
    $variation_ids = NULL;
    if(isset($values['variation_ids'])){
      if (!is_array($values['variation_ids'])) {
        $variation_ids = unserialize($values['variation_ids'], ['allowed_classes' => FALSE]);
      }else{
        $variation_ids = $values['variation_ids'];
      }
    }

    $this->values['variation_ids'] = $variation_ids;

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
