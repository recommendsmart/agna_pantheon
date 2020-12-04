<?php

namespace Drupal\commerce_product_bundles\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the product bundle variation type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_bundle_variation_type",
 *   label = @Translation("Product bundle variation type"),
 *   label_collection = @Translation("Product bundle variation types"),
 *   label_singular = @Translation("product bundle variation type"),
 *   label_plural = @Translation("product bundle variation types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product bundle variation type",
 *     plural = "@count product bundle variation types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce_product_bundles\Access\ProductBundleVariationTypeAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_product_bundles\ProductBundleVariationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationTypeForm",
 *       "edit" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationTypeForm",
 *       "duplicate" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_bundle_variation_type",
 *   admin_permission = "administer commerce_product_bundles_type",
 *   bundle_of = "commerce_bundle_variation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "orderItemType",
 *     "generateTitle",
 *     "traits",
 *     "locked",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/product-bundles-variation-types/add",
 *     "edit-form" = "/admin/commerce/config/product-bundles-variation-types/{commerce_bundle_variation_type}/edit",
 *     "duplicate-form" = "/admin/commerce/config/product-bundles-variation-types/{commerce_bundle_variation_type}/duplicate",
 *     "delete-form" = "/admin/commerce/config/product-bundles-variation-types/{commerce_bundle_variation_type}/delete",
 *     "collection" =  "/admin/commerce/config/product-bundles-variation-types"
 *   }
 * )
 */
class ProductBundleVariationType extends CommerceBundleEntityBase implements ProductBundleVariationTypeInterface {

  /**
   * The order item type ID.
   *
   * @var string
   */
  protected $orderItemType;

  /**
   * Whether the product bundle variation title should be automatically generated.
   *
   * @var bool
   */
  protected $generateTitle;

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return $this->orderItemType;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItemTypeId($order_item_type_id) {
    $this->orderItemType = $order_item_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldGenerateTitle() {
    return (bool) $this->generateTitle;
  }

  /**
   * {@inheritdoc}
   */
  public function setGenerateTitle($generate_title) {
    $this->generateTitle = $generate_title;
    return $this;
  }

}
