<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the product bundle variation type condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_bundle_variation_type",
 *   label = @Translation("Product bundle variation type"),
 *   display_label = @Translation("Product bundle variation types"),
 *   category = @Translation("Commerce Product Bundles"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemBundleVariationType extends ConditionBase {

  use ProductBundleVariationTypeTrait;

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_bundle_variation') {
      return FALSE;
    }

    return in_array($purchased_entity->bundle(), $this->configuration['bundle_variation_types']);
  }

}
