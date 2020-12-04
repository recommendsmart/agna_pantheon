<?php

namespace Drupal\commerce_product_bundles\Service;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ProductBundleVariationReference
 *
 * @package Drupal\commerce_product_bundles\Service
 */
final class ProductBundleVariationReference {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The quantity.
   *
   * @var int
   */
  protected $quantity;

  /**
   * Whether the attribute is required.
   *
   * @var bool
   */
  protected $required;

  /**
   * The attribute values.
   *
   * @var string[]
   */
  protected $ref_variations;

  /**
   * The attribute values.
   *
   * @var string[]
   */
  protected $ref_product;

  /**
   * Constructs a new PreparedAttribute instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['ref_product', 'ref_variations', 'quantity'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    if (!is_array($definition['ref_variations'])) {
      throw new \InvalidArgumentException(sprintf('The property "ref_variations" must be an array.'));
    }

    $this->label = isset($definition['label']) ? $definition['label'] : NULL;
    $this->quantity = $definition['quantity'];
    $this->required = isset($definition['required']) ? $definition['required'] : TRUE;
    $this->ref_variations = $definition['ref_variations'];
    $this->ref_product = $definition['ref_product'];
  }

  /**
   * Gets the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel() {
    if($this->label){
      return $this->label;
    }

    $product = $this->getRefProduct();
    return $product->label();
  }

  /**
   * Gets the element type.
   *
   * @return string
   *   The element type.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets whether the attribute is required.
   *
   * @return bool
   *   TRUE if the attribute is required, FALSE otherwise.
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * Returns ref variations.
   *
   * @param bool $check_access
   * @param null $account
   *
   * @return array
   */
  public function getRefVariations($check_access = TRUE, $account = NULL) {
    if(!$account instanceof AccountInterface){
      $account = \Drupal::currentUser();
    }

    $ref_variations = [];
    foreach ($this->ref_variations as $id){
      $variation = ProductVariation::load($id);
      if ($variation->access('view', $account) && $check_access) {
        $ref_variations[$variation->id()] = $variation;
      }elseif (!$check_access){
        $ref_variations[$variation->id()] = $variation;
      }
    }

    return $ref_variations;
  }

  /**
   * Get ref product.
   *
   * @param bool $product_id
   * @param bool $check_access
   * @param null $account
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed|string[]|null
   */
  public function getRefProduct($product_id = FALSE, $check_access = TRUE, $account = NULL) {
    if(!$account instanceof AccountInterface){
      $account = \Drupal::currentUser();
    }

    $product = Product::load($this->ref_product);
    if ($product->access('view', $account) && $check_access) {
      if($product_id){
        return $this->ref_product;
      }
      return $product;
    }elseif (!$check_access){
      if($product_id){
        return $this->ref_product;
      }
      return $product;
    }

    return NULL;
  }

  /**
   * Gets default ref. product variation.
   * @param bool $variation_id
   *
   * @return mixed|null
   */
  public function getDefaultProductVariation($variation_id = FALSE){
    $product_variations = $this->getRefVariations();

    if($product_variations){
      $default_variation = reset($product_variations);
      if($variation_id){
        return $default_variation->id();
      }
      return $default_variation;
    }

    return NULL;
  }

  /**
   * Gets the array representation of the prepared attribute.
   *
   * @return array
   *   The array representation of the prepared attribute.
   */
  public function toArray() {
    return [
      'label' => $this->label,
      'quantity' => $this->quantity,
      'required' => $this->required,
      'ref_variations' => $this->ref_variations,
      'ref_product' => $this->ref_product
    ];
  }
}
