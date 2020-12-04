<?php

namespace Drupal\commerce_product_bundles\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for Product Bundle Variation creation.
 */
class BundleVariationCreateAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BundleVariationCreateAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to create the product bundle variation.
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle */
    $product_bundle = $route_match->getParameter('commerce_product_bundles');
    if (!$product_bundle) {
      return AccessResult::forbidden();
    }
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('commerce_bundle_variation');
    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_bundles_type');
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $product_type */
    $product_type = $product_type_storage->load($product_bundle->bundle());
    $variation_type_id = $product_type->getBundleVariationTypeId();

    return $access_control_handler->createAccess($variation_type_id, $account, [], TRUE);
  }

}
