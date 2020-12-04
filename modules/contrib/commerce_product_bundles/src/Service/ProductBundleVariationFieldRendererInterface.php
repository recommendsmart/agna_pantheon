<?php

namespace Drupal\commerce_product_bundles\service;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;

/**
 * Interface ProductBundleVariationFieldRendererInterface
 *
 * @package Drupal\commerce_product_bundles\service
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\ProductVariationFieldRendererInterface
 */
interface ProductBundleVariationFieldRendererInterface {

  /**
   * Renders all renderable variation fields.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The product bundle variation.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   Array of render arrays, keyed by field name.
   */
  public function renderFields(ProductBundleVariationInterface $bundle_variation, $view_mode = 'default');

  /**
   * Renders a single bundle variation field.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The product bundle variation.
   * @param string|array $display_options
   *
   * @return array
   *   The render array.
   */
  public function renderField($field_name, ProductBundleVariationInterface $bundle_variation, $display_options = []);

  /**
   * Replaces the rendered bundle variation fields via AJAX.
   *
   * Called by the add to cart form when the selected bundle variation changes.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   * @param string $view_mode
   *
   * @return mixed
   */
  public function replaceRenderedFields(AjaxResponse $response, ProductBundleVariationInterface $bundle_variation, $view_mode = 'default');

}
