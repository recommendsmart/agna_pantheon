<?php

namespace Drupal\commerce_product_bundles\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Publishes a product bundle.
 *
 * @Action(
 *   id = "commerce_publish_product_bundle",
 *   label = @Translation("Publish selected product bundles"),
 *   type = "commerce_product_bundles"
 * )
 */
class PublishProductBundle extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $entity */
    $entity->setPublished(TRUE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $object */
    $result = $object
      ->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
