<?php

namespace Drupal\commerce_product_bundles\Event;

use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;
use Symfony\Component\EventDispatcher\Event;

class ProductBundleVariationEvent extends Event {

  /**
   * The product bundle variation.
   *
   * @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface
   */
  protected $productBundleVariation;

  /**
   * Constructs a new ProductBundleVariationEvent.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $product_variation_variation
   */
  public function __construct(ProductBundleVariationInterface $product_variation_variation) {
    $this->productBundleVariation = $product_variation_variation;
  }

  /**
   * Gets the product bundle variation.
   *
   * @return \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface
   *   The product bundle variation.
   */
  public function getProductBundleVariation() {
    return $this->productBundleVariation;
  }

}
