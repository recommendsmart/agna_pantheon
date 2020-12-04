<?php

namespace Drupal\commerce_product_bundles\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Component\Utility\Html as HtmlUtility;

/**
 * Provides a form input element for Rendering Bundle Variations - referenced variations as radio buttons.
 *
 * Example usage:
 * @code
 * $form['rendered_product_bundle'] = [
 *   '#type' => 'bundle_ref_variations_rendered',
 *   '#title' => $bundle_variation->getLabel(),
 *    '#refItems' => $bundle_variation->getRefVariations(),
 *    '#quantity' => $bundle_variation->getQuantity(),
 *    '#required' => TRUE,
 *    '#options' => [],
 *    '#limit_validation_errors' => [],
 * ];
 * @endcode
 *
 * @FormElement("bundle_ref_variations_rendered")
 */
class BundleRefVariationsRendered extends Radios {

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    if (count($element['#refItems']) > 0) {
      foreach ($element['#refItems'] as $key => $variation) {
        $weight = 0;

        if (isset($element['#default_value']) && $element['#default_value'] == $key) {
          $attributes['class'][] = 'product--bundle--rendered-variations__selected';
        }
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        $element['#options'][$key] = $variation->label();
        $element += [$key => []];
        // Generate the parents as the autogenerator does, so we will have a
        // unique id for each radio button.
        $parents_for_id = array_merge($element['#parents'], [$key]);

        $element[$key] += [
          '#type' => 'radio',
          '#title' => $variation->label(),
          '#return_value' => $key,
          '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : FALSE,
          '#attributes' => [],
          '#parents' => $element['#parents'],
          '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
          '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
          // Errors should only be shown on the parent radios element.
          '#error_no_message' => TRUE,
          '#weight' => $weight,
        ];
      }

      $element['#attributes']['class'][] = 'product-bundles--rendered-variations';
    }

    return $element;
  }

}
