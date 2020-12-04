<?php

namespace Drupal\commerce_product_bundles;

use Drupal\commerce_product_bundles\Entity\ProductBundleInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Interface ProductBundleVariationStorageInterface
 *
 * @package Drupal\commerce_product_bundles
 */
interface ProductBundleVariationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Load bundle variation from context.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle
   *
   * @return mixed
   */
  public function loadFromContext(ProductBundleInterface $product_bundle);

  /**
   * Load enabled bundle variations.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle
   *
   * @return mixed
   */
  public function loadEnabled(ProductBundleInterface $product_bundle);
}
