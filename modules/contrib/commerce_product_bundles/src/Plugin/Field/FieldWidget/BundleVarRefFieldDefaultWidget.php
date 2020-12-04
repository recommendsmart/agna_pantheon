<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'bundle_ref_var_field_default' widget.
 *
 * @FieldWidget(
 *   id = "bundle_ref_var_field_default",
 *   label = @Translation("Commerce product bundle reference variants field widget"),
 *   field_types = {
 *     "bundle_ref_var_field"
 *   },
 * )
 */
class BundleVarRefFieldDefaultWidget extends WidgetBase  {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $values = $item->getValue();

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => FALSE
    ];

    $product_variations = isset($values['product_var_id']) ? $values['product_var_id'] : [];
    $element['product_var_id'] = [
      '#type' => 'select2',
      '#target_type' => 'commerce_product_variation',
      '#title' => $this->t('Product Variations'),
      '#default_value' => $product_variations,
      '#multiple' => FALSE,
      '#autocomplete' => TRUE,
    ];

    // Get default quantity, defaults to 1.
    $quantity = isset($items[$delta]->quantity) ? $items[$delta]->quantity : 1;
    $element['quantity'] = [
      '#type' => 'number',
      '#title' => t('Product variation quantity'),
      '#default_value' => $quantity,
      '#min' => 1,
    ];

    return $element;
  }

}
