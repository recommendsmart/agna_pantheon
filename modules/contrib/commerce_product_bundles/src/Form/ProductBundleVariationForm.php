<?php

namespace Drupal\commerce_product_bundles\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;

/**
 * Class ProductBundleVariationForm
 *
 * @package Drupal\commerce_product_bundles\Form
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\Form\ProductVariationForm
 */
class ProductBundleVariationForm extends ContentEntityForm {

  use EntityDuplicateFormTrait;

  /**
   * {@inheritdoc}
   * @see \Drupal\commerce_product\Form\ProductVariationForm::form()
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Disable client-side validation.
    $form['#attributes']['novalidate'] = 'novalidate';

    return $form;
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\commerce_product\Form\ProductVariationForm::getEntityFromRouteMatch()
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter('commerce_bundle_variation') !== NULL) {
      $entity = $route_match->getParameter('commerce_bundle_variation');
    }
    else {
      /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle */
      $product_bundle = $route_match->getParameter('commerce_product_bundles');
      /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $product_bundle_type */
      $product_bundle_type = $this->entityTypeManager->getStorage('commerce_product_bundles_type')->load($product_bundle->bundle());
      $values = [
        'type' => $product_bundle_type->getBundleVariationTypeId(),
        'product_bundle_id' => $product_bundle->id(),
      ];
      $entity = $this->entityTypeManager->getStorage('commerce_bundle_variation')->create($values);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\commerce_product\Form\ProductVariationForm::save()
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    $this->messenger()->addMessage($this->t('Saved the %label bundle variation.', ['%label' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
