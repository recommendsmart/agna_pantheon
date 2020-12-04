<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'product_bundle_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "product_bundle_field_formatter",
 *   module = "commerce_product_bundles",
 *   label = @Translation("Commerce product bundle field formatter"),
 *   field_types = {
 *     "product_bundle_field"
 *   }
 * )
 */
class CommerceBundleFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Does not actually output anything.
    return [];
  }

}
