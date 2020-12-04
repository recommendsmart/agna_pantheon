<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_bundle_add_to_cart' formatter.
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\Plugin\Field\FieldFormatter\AddToCartFormatter
 *
 * @FieldFormatter(
 *   id = "commerce_bundle_add_to_cart",
 *   label = @Translation("Add to cart bundle form"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class AddToCartBundleFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'combine' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // Allow combining of identical bundle variations in cart.
    $form['combine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine order items containing the same product variation.'),
      '#description' => $this->t('The order item type, referenced product bundle variation, and data from fields exposed on the Add to Cart form must all match to combine.'),
      '#default_value' => $this->getSetting('combine')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('combine')) {
      $summary[] = $this->t('Combine order items containing the same product bundle variation.');
    }
    else {
      $summary[] = $this->t('Do not combine order items containing the same product bundle variation.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $elements[0]['add_to_cart_bundle_form'] = [
      '#lazy_builder' => [
        'commerce_product_bundles.lazy_builders:addToCartBundleForm', [
          $items->getEntity()->id(),
          $this->viewMode,
          $this->getSetting('combine'),
          $langcode,
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $has_cart = \Drupal::moduleHandler()->moduleExists('commerce_cart');
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $has_cart && $entity_type == 'commerce_product_bundles' && $field_name == 'bundle_variations';
  }

}
