<?php

namespace Drupal\commerce_product_bundles\Access;

use Drupal\commerce\CommerceBundleAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ProductBundleVariationTypeAccessControlHandler
 *
 * @package Drupal\commerce_product_bundles\Access
 *
 * @see \Drupal\commerce_product\Access\ProductVariationCollectionAccessCheck
 */
class ProductBundleVariationTypeAccessControlHandler extends CommerceBundleAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view label') {
      $bundle = $entity->id();
      $permissions = [
        'administer commerce_product_bundles',
        'access commerce_product_bundles overview',
        "manage $bundle commerce_bundle_variation",
      ];

      return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
