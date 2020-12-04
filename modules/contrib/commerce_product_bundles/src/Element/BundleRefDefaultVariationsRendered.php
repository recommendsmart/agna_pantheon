<?php

namespace Drupal\commerce_product_bundles\Element;

use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Component\Utility\Html as HtmlUtility;

/**
 * Provides a form input element for Rendering Bundle Variations - referenced variations as radio buttons.
 *
 * Example usage:
 * @code
 * $form['rendered_product_bundle'] = [
 *   '#type' => 'bundle_ref_default_variations_rendered',
 *   '#theme_wrappers' => ['bundle_ref_default_select_wrapper'],
 *   '#title' => 'Bundle',
 *   '#refItems' => [1,2,3],
 *    '#quantity' => 3,
 *    '#required' => TRUE,
 *    '#attribute_title' => NULL,
 *    '#options' => [],
 *    '#limit_validation_errors' => [],
 *    '#ajax' => []
 *  ];
 * ];
 * @endcode
 *
 * @FormElement("bundle_ref_default_variations_rendered")
 */
class BundleRefDefaultVariationsRendered extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processRadios'],
      ],
      '#theme_wrappers' => ['radios'],
      '#pre_render' => [
        [$class, 'preRenderBundleFormElement'],
      ],
    ];
  }

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    if (count($element['#refItems']) > 0) {
      // Get Attributes value if any.
      $attribute_values = self::getAttributeValues($element['#refItems']);
      // Set default indicators.
      $attributeType = NULL;
      $hasAttributes = FALSE;
      $options_available = FALSE;
      $elementName = [];
      // Set weight.
      $weight = 0;

      // Loop through all ref element and construct element.
      foreach ($element['#refItems'] as $key => $variation) {
        // There are available options so set this one to TRUE ;).
        $options_available = TRUE;
        // Define product attribute.
        $product_attribute_values = $attribute_values[$key];

        // Products with attributes logic.
        if ($product_attribute_values[0] instanceof ProductAttributeValue) {
          // Set indicator to true.
          $hasAttributes = TRUE;
          // Get attribute value.
          $markup_attribute = $product_attribute_values[0]->getName();
          $rendered_attribute_all = [];

          // Get type - Defaults to first attribute value.
          $attributeType = $product_attribute_values[0]->getAttributeId();
          $attributeTypeAll = [];
          foreach ($product_attribute_values as $key_attr => $product_attribute_value) {
            $attributeTypeAll[] = $product_attribute_value->getAttributeId();
            $rendered_attribute_all[] = $product_attribute_value->getName();
          }
          // Implode attribute values.
          if($attributeTypeAll) {
            $attributeType = implode(' + ', $attributeTypeAll);
          }
          // Implode attribute values.
          if($rendered_attribute_all) {
            $markup_attribute = implode(' + ', $rendered_attribute_all);
            $elementName[$key] = $markup_attribute;
          }
        } else {
          // If PV has NO attributes use title > by model of 'Product variation title' widget.
          // @see Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationTitleWidget().
          $markup_attribute = $product_attribute_values[0];
        }

        // Mutual logic.
        if (isset($element['#default_value']) && $element['#default_value'] == $key) {
          $attributes['class'][] = 'product--bundle--rendered-variations__selected';
        }
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        // Element options value.
        $element['#options'][$key] = $variation->label();
        $element += [$key => []];
        // Generate the parents as the autogenerator does, so we will have a
        // unique id for each radio button.
        $parents_for_id = array_merge($element['#parents'], [$key]);
        // Get element attributes.
        $attributes = $element['#attributes'];
        if (isset($element['#default_value']) && $element['#default_value'] == $key) {
          $attributes['class'][] = 'product--rendered-attribute__selected';
        }

        // Construct element.
        $element[$key] += [
          '#type' => 'radio',
          '#title' => $markup_attribute,
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

      // Add 'Choose' selection for options with product attributes.
      if($hasAttributes) {
        // Get 'Choose' markup.
        $attribute_name = '';
        if(isset($elementName[$element['#value']])) {
          $attribute_name = $elementName[$element['#value']];
        }

        $html = '<p>' . t('Choose @attr_name: <b>@attr_value</b>', [
            '@attr_name' => str_replace('_', ' ', $attributeType),
            '@attr_value' => $attribute_name
          ]) . '</p>';

        $element['#attribute_title'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $html,
          '#attributes' => [],
          '#weight' => -1,
        ];
        $element['#attributes']['class'][] = 'product-bundles--rendered-variations';
      } else {
        // If PV has no attributes defined.
        $element['#attribute_title'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => t('Select'),
          '#attributes' => [],
          '#weight' => -1,
        ];
        $element['#attributes']['class'][] = 'product-bundles--rendered-variations';
      }

      // If we have no options.
      if (!$options_available) {
        // Do not show title or description if there is no selection.
        $element['#title_display'] = 'invisible';
        $element['#description_display'] = 'invisible';
      }
    }

    return $element;
  }

  /**
   * Gets the attribute values of a given set of variations or PV title if
   * no attributes are defined for this variation.
   *
   * @TODO implement support form multiple product attributes. For now this works only with single attribute.
   *
   * @param array $variations
   *
   * @return array
   */
  protected static function getAttributeValues(array $variations) {
    $values = [];
    foreach ($variations as $variation) {
      // Get attributes value.
      $attribute_values = $variation->getAttributeValues();
      if($attribute_values) {
        foreach ($attribute_values as $attribute_value) {
          if ($attribute_value) {
            $values[$variation->id()][] = $attribute_value;
          }
          else {
            $values['_none'] = '';
          }
        }
      }
      // We are dealing with PV without attributes.
      else {
        $values[$variation->id()][] = $variation->label();
      }
    }

    return $values;
  }

  /**
   * Adds form element theming to an element if its title or description is set.
   * This is used as a pre render function for bundle default ref. el..
   *
   * @param $element
   *
   * @return mixed
   */
  public static function preRenderBundleFormElement($element) {
    // Set the element's title attribute to show #title as a tooltip, if needed.
    if (isset($element['#title']) && $element['#title_display'] == 'attribute') {
      $element['#attributes']['title'] = $element['#title'];
      if (!empty($element['#required'])) {
        // Append an indication that this field is required.
        $element['#attributes']['title'] .= ' (' . t('Required') . ')';
      }
    }

    if (isset($element['#title']) || isset($element['#description'])) {
      // @see #type 'fieldgroup'
      $element['#attributes']['id'] = $element['#id'] . '--wrapper';
      $element['#theme_wrappers'][] = 'bundle_ref_default_select';
      $element['#attributes']['class'][] = 'fieldgroup';
      $element['#attributes']['class'][] = 'form-composite';
    }

    return $element;
  }

}
