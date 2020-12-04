<?php

namespace Drupal\commerce_product_bundles\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for the Product Variation collection route.
 */
class ProductBundleVariationCollectionAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ProductBundleVariationCollectionAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the Product Bundle Variation collection.
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultForbidden
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle */
    $product_bundle = $route_match->getParameter('commerce_product_bundles');
    if (!$product_bundle) {
      return AccessResult::forbidden();
    }
    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_bundles_type');
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $product_bundle_type */
    $product_bundle_type = $product_type_storage->load($product_bundle->bundle());
    if (!$product_bundle_type->allowsMultipleBundleVariations()) {
      return AccessResult::forbidden()->addCacheableDependency($product_bundle_type);
    }

    $variation_type_id = $product_bundle_type->getBundleVariationTypeId();
    $permissions = [
      'administer commerce_product_bundles',
      'access commerce_product_bundles overview',
      "manage $variation_type_id commerce_bundle_variation",
    ];

    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
