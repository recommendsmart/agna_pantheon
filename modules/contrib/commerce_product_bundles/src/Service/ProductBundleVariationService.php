<?php

namespace Drupal\commerce_product_bundles\Service;

use Drupal\commerce\Context;
use Drupal\commerce_order\PriceCalculatorInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariation;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;

/**
 * Class ProductBundleVariationService
 *
 * @package Drupal\commerce_product_bundles\Service
 */
class ProductBundleVariationService implements ProductBundleVariationServiceInterface {

  /**
   * The BundleVaritionFieldMapper.
   *
   * @var \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldRendererInterface
   */
  protected $bundleVariationFieldMapper;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The price calculator.
   *
   * @var \Drupal\commerce_order\PriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * ProductBundleVariationService constructor.
   *
   * @param \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   * @param \Drupal\commerce_order\PriceCalculatorInterface $price_calculator
   */
  public function __construct(ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper, CurrentStoreInterface $current_store, AccountInterface $current_user,
                              ChainPriceResolverInterface $chain_price_resolver, PriceCalculatorInterface $price_calculator) {
    $this->bundleVariationFieldMapper = $bundle_variation_field_mapper;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->chainPriceResolver = $chain_price_resolver;
    $this->priceCalculator = $price_calculator;
  }

  /**
   * {@inheritDoc}
   */
  public function calculateSavings($form_values, $bundle_variation, $check_access = TRUE) {
    $values = $this->getRefVariationsPrice($form_values, $bundle_variation, $check_access);
    $price = $values['price'];
    $variations_calculated_price = $values['variations_calculated_price'];

    return $variations_calculated_price->subtract($price);
  }

  /**
   * {@inheritDoc}
   */
  public function calculateOriginalPrice($form_values, $bundle_variation) {
    $values = $this->getRefVariationsPrice($form_values, $bundle_variation);

    return $values['variations_calculated_price'];
  }

  /**
   * Calculates referenced variations price.
   *
   * @param $form_values
   * @param $bundle_variation
   * @param bool $check_access
   *
   * @return array
   */
  private function getRefVariationsPrice($form_values, $bundle_variation, $check_access = TRUE){
    // Set fallback price for add new eck -> this is for edit form only!
    if($bundle_variation->isNew()){
      $fallback_price = new Price(0, 'USD');
      return [
        'price' => $fallback_price,
        'variations_calculated_price' => $fallback_price
      ];
    }

    $context = new Context($this->currentUser, $this->currentStore->getStore(), NULL, []);
    // Get calculated price > include promotions.
    $bundle_price = $this->priceCalculator->calculate($bundle_variation, 1, $context, ['promotion' => 'promotion']);
    $price = $bundle_price->getCalculatedPrice();
    $variations_calculated_price = new Price(0, $price->getCurrencyCode());
    $bundle_ref_values = [];
    $quantities = [];

    if(isset($form_values[0]['bundle_variations_ref_options'])){
      $bundle_ref_values = $form_values[0]['bundle_variations_ref_options'];
      $quantities = $form_values[0]['quantity'];
    }elseif (isset($form_values[0]['bundle_variations_options'])){
      $bundle_var = ProductBundleVariation::load($form_values[0]['bundle_variations_options']);
      if($bundle_var instanceof ProductBundleVariationInterface){
        $bundle_var_ref = $this->bundleVariationFieldMapper->prepareBundleVariations($bundle_variation);

        // Loop through all ref product to get correct price.
        if(!empty($bundle_var_ref)){
          foreach ($bundle_var_ref as $ref_key => $product_ref){
            $product_id = $product_ref->getRefProduct(TRUE);
            $variations_ref = $product_ref->getRefVariations();
            // Select only first variation.
            if(!empty($variations_ref)){
              $variation_ref = reset($variations_ref);
              $bundle_ref_values[$product_id] = $variation_ref;
              $quantities[$product_id] = $product_ref->getQuantity();
            }
          }
        }
      }
    }else{
      $bundle_var_ref = $this->bundleVariationFieldMapper->prepareBundleVariations($bundle_variation, $check_access);

      // Loop through all ref product to get correct price.
      if(!empty($bundle_var_ref)){
        foreach ($bundle_var_ref as $ref_key => $product_ref){
          $product_id = $product_ref->getRefProduct(TRUE, $check_access);
          $variations_ref = $product_ref->getRefVariations($check_access);
          // Select only first variation.
          if(!empty($variations_ref)){
            $variation_ref = reset($variations_ref);
            $bundle_ref_values[$product_id] = $variation_ref;
            $quantities[$product_id] = $product_ref->getQuantity();
          }
        }
      }
    }

    // Loop through selected variations.
    foreach ($bundle_ref_values as $product_id => $variation_id){
      $variation = NULL;
      if(is_numeric($variation_id)){
        $variation = ProductVariation::load($variation_id);
      }
      elseif($variation_id instanceof ProductVariationInterface){
        // Use only first variation for calculation.
        $variation = $variation_id;
      }

      // Double check if variation is loaded.
      if($variation instanceof ProductVariationInterface){
        $variation_price = $variation->getPrice();
        // Resolve price.
        if ($variation_price->getCurrencyCode() !== $price->getCurrencyCode()) {
          $context = new Context($this->currentUser, $this->currentStore->getStore(), NULL, []);
          $variation_price = $this->chainPriceResolver->resolve($variation, 1, $context);
        }
        // Defaults to one.
        $multiply = 1;
        if (isset($quantities[$product_id])) {
          $multiply = $quantities[$product_id];
        }
        $multiplied = $variation_price->multiply($multiply);
        $variations_calculated_price = $variations_calculated_price->add($multiplied);
      }
    }

    return [
      'price' => $price,
      'variations_calculated_price' => $variations_calculated_price
    ];
  }

}
