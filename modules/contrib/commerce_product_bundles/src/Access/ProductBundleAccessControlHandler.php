<?php

namespace Drupal\commerce_product_bundles\Access;

use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for Commerce Product Bundle.
 *
 * @see \Drupal\block_content\Entity\BlockContent
 */
class ProductBundleAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * ProductBundleAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   */
  public function __construct(EntityTypeInterface $entity_type, CurrentStoreInterface $current_store) {
    parent::__construct($entity_type);
    $this->currentStore = $current_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('commerce_store.current_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $entity */
    if ($operation === 'view') {

      // Allow view access for users with 'Access the products bundles overview page'
      if($account->hasPermission('access commerce_product_bundles overview')){
        return AccessResult::allowed()->addCacheableDependency($entity);
      }

      // Get stores.
      $stores = $entity->get('stores')->referencedEntities();

      // Check if product has current store.
      // If not set access to false!
      if(!in_array($this->currentStore->getStore(), $stores)){
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }

      // If ECK has publish/unpublished permission.
      // If ECK is unpublished check if user has permission to view unpublished ECK.
      if (!$entity->isPublished()) {
        return AccessResult::allowedIfHasPermission($account, 'view unpublished commerce_product_bundles entities')->addCacheableDependency($entity);
      }

      return AccessResult::allowed()->addCacheableDependency($entity);
    }

    // Pass to parent.
    return parent::checkAccess($entity, $operation, $account);
  }


}
