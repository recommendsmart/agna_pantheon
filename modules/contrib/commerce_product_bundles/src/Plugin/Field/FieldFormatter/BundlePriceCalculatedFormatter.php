<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use Drupal\commerce_order\Plugin\Field\FieldFormatter\PriceCalculatedFormatter;

/**
 * Plugin implementation of the 'commerce_bundle_price_calculated' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_bundle_price_calculated",
 *   label = @Translation("Calculated Bundle Price"),
 *   field_types = {
 *     "commerce_currencies_price"
 *   }
 * )
 */
class BundlePriceCalculatedFormatter extends PriceCalculatedFormatter {

}
