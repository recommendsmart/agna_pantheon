<?php

namespace Drupal\commerce_product_bundles\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a product bundle.
 *
 * @Action(
 *   id = "commerce_unpublish_product_bundle",
 *   label = @Translation("Unpublish selected product bundles"),
 *   type = "commerce_product_bundles"
 * )
 */
class UnpublishProductBundle extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $entity */
    $entity->setUnpublished();
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $object */
    $access = $object
      ->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
