<?php

namespace Drupal\commerce_product_bundles\Element;

use Drupal\commerce_order\PriceCalculatorResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariation;

/**
 * Provides a form input element for Rendering Bundle Variations as radio buttons.
 *
 * Example usage:
 * @code
 * $form['rendered_product_bundle'] = [
 *   '#type' => 'commerce_product_bundles_rendered',
 *   '#title' => $product_bundle->label(),
 *   '#options' => [1 => 'Bundle variation, 2 => Bundle variation 2],
 *    '#required' => TRUE,
 *    '#default_value' => $selected_variation->id(),
 *    '#limit_validation_errors' => [],
 *    '#ajax' => []
 * ];
 * @endcode
 *
 * @FormElement("commerce_product_bundles_rendered")
 */
class CommerceProductBundleRendered extends Radios {

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    if (count($element['#options']) > 0) {
      foreach ($element['#options'] as $key => $bundle) {
        $weight = 0;
        $bundle_variation = ProductBundleVariation::load($key);
        if (isset($element['#default_value']) && $element['#default_value'] == $key) {
          $attributes['class'][] = 'product--bundle--rendered-variations__selected';
        }
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        $element += [$key => []];
        // Generate the parents as the autogenerator does, so we will have a
        // unique id for each radio button.
        $parents_for_id = array_merge($element['#parents'], [$key]);

        // Get default label value.
        $label = $bundle_variation->label();

        // Get savings.
        $service = \Drupal::service('commerce_product_bundles.bundle_variation_service');
        $savings = $service->calculateSavings([], $bundle_variation);

        // Show savings only if it is higher that 0.
        if($savings->isPositive() && !$savings->isZero()) {
          $options = [
            'currency_display' => 'symbol',
            'minimum_fraction_digits' => 0,
          ];

          $calculated_savings_result = new PriceCalculatorResult($savings, $savings);
          $cal_savings_price = $calculated_savings_result->getCalculatedPrice();
          $cal_savings_original_number = $cal_savings_price->getNumber();
          $calc_savings_base_number = $calculated_savings_result->getBasePrice()->getNumber();
          $calc_savings_currency_code = $cal_savings_price->getCurrencyCode();

          $savings_price = [
            '#theme' => 'commerce_savings_price_calculated',
            '#result' => $calculated_savings_result,
            '#calculated_price' => \Drupal::service('commerce_price.currency_formatter')->format($cal_savings_original_number, $calc_savings_currency_code, $options),
            '#base_price' => \Drupal::service('commerce_price.currency_formatter')->format($calc_savings_base_number, $calc_savings_currency_code, $options),
            '#adjustments' => $calculated_savings_result->getAdjustments(),
            '#cache' => [
              'tags' => $bundle_variation->getCacheTags(),
              'contexts' => Cache::mergeContexts($bundle_variation->getCacheContexts(), [
                'languages:' . LanguageInterface::TYPE_INTERFACE,
                'country',
              ]),
            ],
          ];

          $savings_markup = [
            '#theme' => 'commerce_bundle_savings_label',
            '#savings' => $savings_price,
            '#label' => $label
          ];

          // Render label.
          $label = \Drupal::service('renderer')->render($savings_markup);
        }

        $element[$key] += [
          '#type' => 'radio',
          '#title' => $label,
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
