<?php

namespace Drupal\commerce_product_bundles\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the product bundle type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_bundles_type",
 *   label = @Translation("Product Bundle type"),
 *   label_collection = @Translation("Product Bundle types"),
 *   label_singular = @Translation("product bundle type"),
 *   label_plural = @Translation("product bundle types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product bundle type",
 *     plural = "@count product bundle types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce\CommerceBundleAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_product_bundles\ProductBundleTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product_bundles\Form\ProductBundleTypeForm",
 *       "edit" = "Drupal\commerce_product_bundles\Form\ProductBundleTypeForm",
 *       "duplicate" = "Drupal\commerce_product_bundles\Form\ProductBundleTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_bundles_type",
 *   admin_permission = "administer commerce_product_bundles_type",
 *   bundle_of = "commerce_product_bundles",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "bundleVariationType",
 *     "multipleBundleVariations",
 *     "injectBundleVariationFields",
 *     "traits",
 *     "locked",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/product-bundles-types/add",
 *     "edit-form" = "/admin/commerce/config/product-bundles-types/{commerce_product_bundles_type}/edit",
 *     "duplicate-form" = "/admin/commerce/config/product-bundlestypes/{commerce_product_bundles_type}/duplicate",
 *     "delete-form" = "/admin/commerce/config/product-bundles-types/{commerce_product_bundles_type}/delete",
 *     "collection" = "/admin/commerce/config/product-bundles-types"
 *   }
 * )
 */
class ProductBundleType extends CommerceBundleEntityBase implements ProductBundleTypeInterface {

  /**
   * The product bundle type description.
   *
   * @var string
   */
  protected $description;

  /**
   * The bundle variation type ID.
   *
   * @var string
   */
  protected $bundleVariationType;

  /**
   * Whether bundle products of this type can have multiple variations.
   * Always set to TRUE!
   *
   * @var bool
   */
  protected $multipleBundleVariations = TRUE;

  /**
   * Whether bundle variation fields should be injected.
   *
   * @var bool
   */
  protected $injectBundleVariationFields = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleVariationTypeId() {
    return $this->bundleVariationType;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundleVariationTypeId($bundle_variation_type_id) {
    $this->bundleVariationType = $bundle_variation_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function allowsMultipleBundleVariations() {
    return $this->multipleBundleVariations;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultipleBundleVariations($multiple_bundle_variations) {
    $this->multipleBundleVariations = $multiple_bundle_variations;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldInjectBundleVariationFields() {
    return $this->injectBundleVariationFields;
  }

  /**
   * {@inheritdoc}
   */
  public function setInjectBundleVariationFields($inject) {
    $this->injectBundleVariationFields = (bool) $inject;
    return $this;
  }

}
