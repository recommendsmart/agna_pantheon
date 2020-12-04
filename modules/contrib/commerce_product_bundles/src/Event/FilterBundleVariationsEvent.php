<?php

namespace Drupal\commerce_product_bundles\Event;

use Drupal\commerce_product_bundles\Entity\ProductBundleInterface;
use Symfony\Component\EventDispatcher\Event;

class FilterBundleVariationsEvent extends Event {

  /**
   * The parent product bundle.
   *
   * @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface
   *
   */
  protected $productBundle;

  /**
   * The enabled bundle variations.
   *
   * @var array
   */
  protected $bundleVariations;

  /**
   * FilterBundleVariationsEvent constructor.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle
   * @param array $bundle_variations
   */
  public function __construct(ProductBundleInterface $product_bundle, array $bundle_variations) {
    $this->productBundle = $product_bundle;
    $this->bundleVariations = $bundle_variations;
  }

  /**
   * Gets the parent product bundle.
   *
   * @return \Drupal\commerce_product_bundles\Entity\ProductBundleInterface
   */
  public function getProductBundle() {
    return $this->productBundle;
  }

  /**
   * Gets the enabled bundle variations.
   *
   * @return array
   *   The enabled bundle variations.
   */
  public function getVariations() {
    return $this->bundleVariations;
  }

  /**
   * Sets the enabled bundle variations.
   *
   * @param array $bundle_variations
   *   The enabled bundle variations.
   *
   * @return $this
   */
  public function setBundleVariations(array $bundle_variations) {
    $this->bundleVariations = $bundle_variations;
    return $this;
  }

}
