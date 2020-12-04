<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the product bundle type condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_product_bundle_type",
 *   label = @Translation("Product Bundle type"),
 *   display_label = @Translation("Order contains product bundle types"),
 *   category = @Translation("Commerce Product Bundles"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderProductBundleType extends ConditionBase {

  use ProductBundleTypeTrait;

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
      $product_type = $purchased_entity->getProduct()->bundle();
      if (in_array($product_type, $this->configuration['product_bundle_types'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
