<?php

namespace Drupal\commerce_product_bundles\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for product bundle types.
 */
interface ProductBundleTypeInterface extends CommerceBundleEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the product bundle type's matching variation type ID.
   *
   * @return string
   *   The bundle variation type ID.
   */
  public function getBundleVariationTypeId();

  /**
   * Sets the product bundle type's matching bundle variation type ID.
   *
   * @param string $bundle_variation_type_id
   *   The bundle variation type ID.
   *
   * @return $this
   */
  public function setBundleVariationTypeId($bundle_variation_type_id);

  /**
   * Gets whether products bundle of this type can have multiple bundle variations.
   *
   * @return bool
   *   TRUE if products bundle of this type can have multiple bundle variations,
   *   FALSE otherwise.
   */
  public function allowsMultipleBundleVariations();

  /**
   * Sets whether products bundle of this type can have multiple bundle variations.
   *
   * @param bool $multiple_bundle_variations
   *   Whether bundle products of this type can have multiple bundle variations.
   *
   * @return $this
   */
  public function setMultipleBundleVariations($multiple_bundle_variations);

  /**
   * Gets whether bundle variation fields should be injected into the rendered product bundle.
   *
   * @return bool
   *   TRUE if the bundle variation fields should be injected into the rendered
   *   product bundle, FALSE otherwise.
   */
  public function shouldInjectBundleVariationFields();

  /**
   * Sets whether bundle variation fields should be injected into the rendered product bundle.
   *
   * @param bool $inject
   *   Whether bundle variation fields should be injected into the rendered product bundle.
   *
   * @return $this
   */
  public function setInjectBundleVariationFields($inject);

}
