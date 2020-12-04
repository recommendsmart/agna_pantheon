<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the product bundle variation type condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_bundle_variation_type",
 *   label = @Translation("Product bundle variation type"),
 *   display_label = @Translation("Order contains product bundle variation types"),
 *   category = @Translation("Commerce Product Bundles"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderBundleVariationType extends ConditionBase {

  use ProductBundleVariationTypeTrait;

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    foreach ($order->getItems() as $order_item) {
      /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $purchased_entity */
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_bundle_variation') {
        continue;
      }
      if (in_array($purchased_entity->bundle(), $this->configuration['bundle_variation_types'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
