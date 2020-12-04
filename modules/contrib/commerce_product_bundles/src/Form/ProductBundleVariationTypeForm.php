<?php

namespace Drupal\commerce_product_bundles\Form;

use Drupal\commerce\EntityHelper;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Class ProductBundleVariationTypeForm
 *
 * @package Drupal\commerce_product_bundles\Form
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\Form\ProductVariationTypeForm
 *
 */
class ProductBundleVariationTypeForm extends CommerceBundleEntityFormBase {

  use EntityDuplicateFormTrait;

  /**
   * {@inheritdoc}
   *
   *  @see \Drupal\commerce_product\Form\ProductVariationTypeForm::form()
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationTypeInterface $bundle_variation_type */
    $bundle_variation_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bundle_variation_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bundle_variation_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product_bundles\Entity\ProductBundleVariationType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$bundle_variation_type->isNew(),
    ];

    $form = $this->buildTraitForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('commerce_order')) {
      // Prepare a list of order item types used to purchase product variations.
      $order_item_type_storage = $this->entityTypeManager->getStorage('commerce_order_item_type');
      $order_item_types = $order_item_type_storage->loadMultiple();
      $order_item_types = array_filter($order_item_types, function ($order_item_type) {
        /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
        return $order_item_type->getPurchasableEntityTypeId() == 'commerce_bundle_variation';
      });

      $form['orderItemType'] = [
        '#type' => 'select',
        '#title' => $this->t('Order item type'),
        '#default_value' => $bundle_variation_type->getOrderItemTypeId(),
        '#options' => EntityHelper::extractLabels($order_item_types),
        '#empty_value' => '',
        '#required' => TRUE,
      ];
    }

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_bundle_variation',
          'bundle' => $bundle_variation_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product_variation', $bundle_variation_type->id()),
      ];
      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   *  @see \Drupal\commerce_product\Form\ProductVariationTypeForm::validateForm()
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateTraitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   *  @see \Drupal\commerce_product\Form\ProductVariationTypeForm::save()
   */
  public function save(array $form, FormStateInterface $form_state) {
    $product_bundle_variation_type = $this->entity;
    $product_bundle_variation_type->save();

    $this->postSave($product_bundle_variation_type, $this->operation);
    $this->submitTraitForm($form, $form_state);

    // Create bundle image field.
    if ($this->operation == 'add') {
      commerce_product_bundles_add_variations_field($product_bundle_variation_type);
    }

    $this->messenger()->addMessage($this->t('Saved the %label product bundle variation type.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_bundle_variation_type.collection');
  }

}
