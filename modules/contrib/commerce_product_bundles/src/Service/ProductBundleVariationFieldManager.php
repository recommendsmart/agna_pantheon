<?php

namespace Drupal\commerce_product_bundles\Service;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;

/**
 * Class ProductBundleVariationFieldManager
 *
 * @package Drupal\commerce_product_bundles\Service
 *
 */
class ProductBundleVariationFieldManager implements ProductBundleVariationFieldManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function prepareBundleVariations(ProductBundleVariationInterface $selected_bundle_variation, $check_access = TRUE, $account = NULL) {
    $products = [];

    $bundle_ref_eck = $this->getBundleValues($selected_bundle_variation);

    foreach ($bundle_ref_eck as $key_ref => $val) {
      $definition = [
        'quantity' => $val['quantity'],
        'ref_variations' => $val['variation_ids'],
        'ref_product' => $val['product_id']
      ];

      // Do not construct object if we do not have required values!
      if(!empty($definition['ref_variations']) && !empty($definition['ref_product'])){
        $ref_product_eck = new ProductBundleVariationReference($definition);

        if($ref_product_eck->getRefProduct(FALSE, $check_access, $account) instanceof ProductInterface){
          $products[$val['product_id']] = $ref_product_eck;
        }
      }
    }

    return $products;
  }

  /**
   * Gets the product bundle variation ref. PV.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *
   * @return array
   */
  protected function getBundleValues(ProductBundleVariationInterface $bundle_variation) {
    $values = [];
    $variation_values = $bundle_variation->getProductVariations();
    foreach ($variation_values as $key => $value) {
      $values[] = [
        'product_id' => $value['product_id'],
        'variation_ids' => $value['variation_ids'],
        'quantity' => $value['quantity']
      ];
    }

    return $values;
  }
}
