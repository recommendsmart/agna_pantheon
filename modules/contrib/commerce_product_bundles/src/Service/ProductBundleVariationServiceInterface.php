<?php

namespace Drupal\commerce_product_bundles\Service;

/**
 * Interface ProductBundleVariationServiceInterface
 *
 * @package Drupal\commerce_product_bundles\Service
 */
interface ProductBundleVariationServiceInterface {

  /**
   * Calculates 'savings' field.
   *
   * @param $form_values
   * @param $bundle_variation
   *
   * @return mixed
   */
  public function calculateSavings($form_values, $bundle_variation);

  /**
   * Calculate 'original price' field value.
   *
   * @param $form_values
   * @param $bundle_variation
   *
   * @return mixed
   */
  public function calculateOriginalPrice($form_values, $bundle_variation);

}
