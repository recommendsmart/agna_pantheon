<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'bundle_ref_var_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bundle_ref_var_field_formatter",
 *   module = "commerce_product_bundles",
 *   label = @Translation("Commerce product bundle reference variants field formatter"),
 *   field_types = {
 *     "bundle_ref_var_field"
 *   }
 * )
 */
class BundleVarRefFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if (empty($items)) {
      return $elements;
    }

    foreach ($items->getValue() as $delta => $item) {
      $product_variation = ProductVariation::load($item['product_var_id']);
      $qta = $item['quantity'];

      if($product_variation instanceof ProductVariationInterface){
        $elements[$delta] = [
          '#markup' => implode(' ', [$product_variation->getTitle(), $qta])
        ];
      }
    }

    return $elements;
  }

}
