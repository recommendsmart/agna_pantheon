<?php

namespace Drupal\commerce_product\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to view a product bundle variation.
 *
 * Code was taken form and modified:
 * @see \Drupal\commerce_product\Plugin\views\field\ProductVariationViewLink
 *
 * @ViewsField("commerce_bundle_variation_view_link")
 */
class ProductBundleVariationViewLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    return $this->getEntity($row)->toUrl('canonical')->setAbsolute($this->options['absolute']);
  }

}
