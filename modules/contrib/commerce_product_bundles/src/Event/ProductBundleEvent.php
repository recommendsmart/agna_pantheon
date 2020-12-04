<?php

namespace Drupal\commerce_product_bundles\Event;

use Drupal\commerce_product_bundles\Entity\ProductBundleInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProductBundleEvent
 *
 * @package Drupal\commerce_product_bundles\Event
 */
class ProductBundleEvent extends Event {

  /**
   * The product bundle.
   *
   * @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface
   */
  protected $product_bundle;

  /**
   * Constructs a new ProductBundleEvent.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle
   *   The product bundle.
   */
  public function __construct(ProductBundleInterface $product_bundle) {
    $this->product_bundle = $product_bundle;
  }

  /**
   * Gets the product bundle.
   *
   * @return \Drupal\commerce_product_bundles\Entity\ProductBundleInterface
   *   The product. bundle
   */
  public function getProductBundle() {
    return $this->product_bundle;
  }

}
