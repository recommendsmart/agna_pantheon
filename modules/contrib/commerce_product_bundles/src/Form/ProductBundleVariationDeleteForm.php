<?php

namespace Drupal\commerce_product_bundles\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Class ProductBundleVariationDeleteForm
 *
 * @package Drupal\commerce_product_bundles\Form
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\Form\ProductVariationDeleteForm
 */
class ProductBundleVariationDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   *  @see \Drupal\commerce_product\Form\ProductVariationDeleteForm::getQuestion()
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %label bundle variation?', [
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   *  @see \Drupal\commerce_product\Form\ProductVariationDeleteForm::getDeletionMessage()
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The %label bundle variation has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   *  @see \Drupal\commerce_product\Form\ProductVariationDeleteForm::logDeletionMessage()
   */
  protected function logDeletionMessage() {
    $entity = $this->getEntity();
    $this->logger($entity->getEntityType()->getProvider())->notice('The %label bundle variation has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

}
