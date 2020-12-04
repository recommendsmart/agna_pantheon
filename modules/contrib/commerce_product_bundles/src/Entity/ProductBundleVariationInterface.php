<?php

namespace Drupal\commerce_product_bundles\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Defines the interface for product bundle variations.
 */
interface ProductBundleVariationInterface extends PurchasableEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the parent product bundle.
   *
   * @return \Drupal\commerce_product_bundles\Entity\ProductBundle|null
   *   The product bundle entity, or null.
   */
  public function getBundleProduct();

  /**
   * Gets the parent product bundle ID.
   *
   * @return int|null
   *   The product bundle ID, or null.
   */
  public function getBundleProductId();

  /**
   * Gets Referenced bundle variation commerce product variations ids.
   *
   * @return mixed
   */
  public function getProductVariationsIds();

  /**
   * Gets Referenced bundle variation commerce product variations.
   *
   * @return mixed
   */
  public function getProductVariations();

  /**
   * Sets Referenced bundle variation commerce product variations.
   *
   * @param array $variations
   *
   * @return mixed
   */
  public function setProductVariations(array $variations);

  /**
   * Gets the variation bundle title.
   *
   * @return string
   *   The variation bundle title
   */
  public function getTitle();

  /**
   * Sets the variation bundle title.
   *
   * @param string $title
   *   The variation bundle title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Sets the price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return $this
   */
  public function setPrice(Price $price);

  /**
   * Gets whether the bundle variation is active.
   *
   * @return bool
   *   TRUE if the variation bundle is active, FALSE otherwise.
   */
  public function isActive();

  /**
   * Sets whether the bundle variation is active.
   *
   * @param bool $active
   *   Whether the bundle variation is active.
   *
   * @return $this
   */
  public function setActive($active);

  /**
   * Gets the bundle variation creation timestamp.
   *
   * @return int
   *   The bundle variation creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the bundle variation creation timestamp.
   *
   * @param int $timestamp
   *   The bundle variation creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);


}
