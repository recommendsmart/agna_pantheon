<?php

namespace Drupal\commerce_product_bundles\Access;

use Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an access control handler for Product Bundle Variations.
 */
class ProductBundleVariationAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManager
   */
  protected $bundleVariationMapper;

  /**
   * ProductBundleVariationAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface $bundle_variation_mapper
   */
  public function __construct(EntityTypeInterface $entity_type, ProductBundleVariationFieldManagerInterface $bundle_variation_mapper) {
    parent::__construct($entity_type);
    $this->bundleVariationMapper = $bundle_variation_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('commerce_product_bundles.bundle_variation_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $bundle = $entity->bundle();

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $entity */
    $product_bundle = $entity->getBundleProduct();
    // If parent product is NOT available do not show bundle variations.
    if (!$product_bundle instanceof ProductBundleInterface) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    if (!$product_bundle->access('view', $account)) {
      // If we do not have access to parent product do not allow access to variation.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $entity */
    if ($operation === 'view') {

      // Allow view access for users with 'Access the products bundles overview page'
      if($account->hasPermission('access commerce_product_bundles overview')){
        return AccessResult::allowed()->addCacheableDependency($entity);
      }

      // Check if ref. variations are restricted.
      if(!$this->checkRefVariationsAccess($entity, $account)){
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }

      return AccessResult::allowedIfHasPermission($account, "view $bundle commerce_product_bundles");
    }

    return AccessResult::allowedIfHasPermission($account, "manage $bundle commerce_bundle_variation");
  }

  /**
   * Check access to bundle variation based on ref. variations.
   * If one variation has no access bundle has no access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  protected function checkRefVariationsAccess(EntityInterface $entity, AccountInterface $account){
    $bundle_var_ref = $this->bundleVariationMapper->prepareBundleVariations($entity, TRUE, $account);
    $referenced_products = $entity->getProductVariations();

    // If we do not have access to one or more ref. products, deny access!
    if(count($referenced_products) !== count($bundle_var_ref)){
      return FALSE;
    }

    if($bundle_var_ref){
      foreach ($bundle_var_ref as $product_id => $variation){
        $ref_variations = $variation->getRefVariations(TRUE, $account);
        $variations_array = $variation->toArray();
        if(count($ref_variations) < count($variations_array['ref_variations'])) {
          return FALSE;
        }
      }
    }
    // If there is no available product variation deny access.
    else {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Create access depends on the "manage" permission because the full entity
    // is not passed, making it impossible to determine the parent product.
    $result = AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      "manage $entity_bundle commerce_bundle_variation",
    ], 'OR');

    return $result;
  }

}
