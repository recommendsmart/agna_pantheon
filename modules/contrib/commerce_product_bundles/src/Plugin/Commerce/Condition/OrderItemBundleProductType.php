<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the product bundle type condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_bundle_product_type",
 *   label = @Translation("Product Bundle type"),
 *   display_label = @Translation("Product Bundle types"),
 *   category = @Translation("Commerce Product Bundles"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemBundleProductType extends ConditionBase {

  use ProductBundleTypeTrait;

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
    $product_type = $purchased_entity->getBundleProduct()->bundle();

    return in_array($product_type, $this->configuration['product_bundle_types']);
  }

}
