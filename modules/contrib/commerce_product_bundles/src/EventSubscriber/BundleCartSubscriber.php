<?php

namespace Drupal\commerce_product_bundles\EventSubscriber;

use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BundleCartSubscriber
 *
 * @package Drupal\commerce_product_bundles\EventSubscriber
 */
class BundleCartSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::ORDER_ITEM_COMPARISON_FIELDS => ['onBundleCartComparison'],
    ];
    return $events;
  }

  /**
   * Add ref. variation field to comparison for bundles.
   * Allow bundles to be combined in cart.
   *
   * @param \Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent $event
   */
  public function onBundleCartComparison(OrderItemComparisonFieldsEvent $event) {
    $order_item = $event->getOrderItem();
    $purchasable_eck = $order_item->getPurchasedEntity();
    if ($purchasable_eck instanceof ProductBundleVariationInterface) {
      $event->setComparisonFields(['field_product_variation_ref']);
    }
  }

}
