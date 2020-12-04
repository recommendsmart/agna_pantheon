<?php

namespace Drupal\commerce_product_bundles\Entity;

use Drupal\commerce_store\Entity\EntityStoresInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for Product Bundles.
 */
interface ProductBundleInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface, EntityStoresInterface {

  /**
   * Gets the product bundle title.
   *
   * @return string
   *   The product bundle title
   */
  public function getTitle();

  /**
   * Sets the product bundle title.
   *
   * @param string $title
   *   The product bundle title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Gets the product bundle creation timestamp.
   *
   * @return int
   *   The product bundle creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the product bundle creation timestamp.
   *
   * @param int $timestamp
   *   The product bundle creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the bundle variation IDs.
   * Leave Variations instead of BundleVariations because of commerce_product compatibility.
   *
   * @return int[]
   *   The bundle variation IDs.
   */
  public function getVariationIds();

  /**
   * Gets the bundle variations.
   * Leave Variations instead of BundleVariations because of commerce_product compatibility.
   *
   * @return \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface[]
   *   The bundle variations.
   */
  public function getVariations();

  /**
   * Sets the bundle variations.
   * Leave Variations instead of BundleVariations because of commerce_product compatibility.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface[] $bundle_variations
   *   The bundle variations.
   *
   * @return $this
   */
  public function setVariations(array $bundle_variations);

  /**
   * Gets whether the product bundle has bundle variations.
   * Leave Variations instead of BundleVariations because of commerce_product compatibility.
   *
   * @return bool
   *   TRUE if the product bundle has bundle variations, FALSE otherwise.
   */
  public function hasVariations();

  /**
   * Adds a bundle variation.
   * Leave Variations instead of BundleVariations because of commerce_product compatibility.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The bundle variation.
   *
   * @return $this
   */
  public function addVariation(ProductBundleVariationInterface $bundle_variation);

  /**
   * Removes a bundle variation.
   * Leave Variations instead of BundleVariations because of commerce_product compatibility.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The bundle variation.
   *
   * @return $this
   */
  public function removeVariation(ProductBundleVariationInterface $bundle_variation);

  /**
   * Checks whether the product bundle has a given bundle variation.
   * Leave Variations instead of BundleVariations because of commerce_product compatibility.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The bundle variation.
   *
   * @return bool
   *   TRUE if the bundle variation was found, FALSE otherwise.
   */
  public function hasVariation(ProductBundleVariationInterface $bundle_variation);

  /**
   * Gets the default bundle variation.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface|null
   *   The default bundle variation, or NULL if none found.
   */
  public function getDefaultVariation();

}
