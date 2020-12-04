<?php

namespace Drupal\commerce_product_bundles\Service;

use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;

/**
 * Interface ProductBundleVariationFieldManagerInterface
 *
 * @package Drupal\commerce_product_bundles\Service
 */
interface ProductBundleVariationFieldManagerInterface {

  /**
   * Creates ProductBundleVariationReference eck as an preparation for bundle variations switcher.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $selected_bundle_variation
   * @param bool $check_access
   * @param null $account
   *
   * @return mixed
   */
  public function prepareBundleVariations(ProductBundleVariationInterface $selected_bundle_variation, $check_access = TRUE, $account = NULL);

}
