<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldWidget;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'product_bundle_field_default' widget.
 *
 * @FieldWidget(
 *   id = "product_bundle_field_default",
 *   label = @Translation("Commerce product bundle field widget"),
 *   field_types = {
 *     "product_bundle_field"
 *   },
 * )
 */
class CommerceBundleFieldDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CommerceBundleFieldDefaultWidget constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

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

    $products = isset($values['product_id']) ? $values['product_id'] : [];
    $element['product_id'] = [
      '#type' => 'select2',
      '#target_type' => 'commerce_product',
      '#title' => $this->t('Products'),
      '#default_value' => $products,
      '#multiple' => FALSE,
      '#autocomplete' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxGetProductVariations'],
        'event' => 'change',
        'wrapper' => 'product-variation--wrapper-' . $delta,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
    ];

    $default_product = $products;
    if ($form_state->isRebuilding()) {
      $parents = array_merge($element['#field_parents'], [$items->getName(), $delta, 'product_id']);
      $selected_product_id = (array) NestedArray::getValue($form_state->getUserInput(), $parents);
      if(!empty($selected_product_id)){
        $default_product = reset($selected_product_id);
      }
    }

    // Get default options for product variations select.
    $product_variations = $this->entityTypeManager->getStorage('commerce_product_variation')->getQuery()
      ->accessCheck(FALSE)
      ->condition('default_langcode', 1);

    // If we have default product selected limit options.
    if (!empty($default_product)){
      $product_variations->condition('product_id', $default_product);
    }

    $variations = $product_variations->execute();

    $default_options = self::getVariationOptions($variations);

    // Get default variations.
    // Construct prefix: if we have default value show field, if not hide it.
    $variations_default = isset($values['variation_ids']) ? $values['variation_ids'] : [];
    $prefix = '<div id="product-variation--wrapper-' . $delta . '" class="hidden">';
    if(!empty($variations_default) || !empty($default_product)){
      $prefix = '<div id="product-variation--wrapper-' . $delta . '">';
    }

    $element['variation_ids'] = [
      '#type' => 'select2',
      '#options' => $default_options,
      '#title' => $this->t('Product Variations'),
      '#default_value' => $variations_default,
      '#multiple' => TRUE,
      '#prefix' => $prefix,
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxSetProductVariations'],
        'event' => 'change',
        'wrapper' => 'product-variation--wrapper', // This element is updated with this AJAX callback.
      ],
    ];

    // Get default quantity, defaults to 1.
    $quantity = isset($items[$delta]->quantity) ? $items[$delta]->quantity : 1;
    $element['quantity'] = [
      '#type' => 'number',
      '#title' => t('Product quantity'),
      '#default_value' => $quantity,
      '#min' => 1,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Helper function for getting variation options for select 2 element.
   *
   * @param $variations
   *
   * @return array
   */
  private static function getVariationOptions($variations){
    $options = [];
    foreach ($variations as $variation_id){
      $product_variation_eck = ProductVariation::load($variation_id);
      $options[$variation_id] = $product_variation_eck->label();
    }

    return $options;
  }

  /**
   * Ajax callback.
   *
   * Shows product variations field based on selected product.
   */
  public static function ajaxGetProductVariations(array $form, FormStateInterface $form_state) {
    $triggering_el = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_el['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    $wrapper_id = $triggering_el['#ajax']['wrapper'];
    $delta = $element['#delta'];
    $selected_value = $form_state->getValue($parents[0]);

    // Get selected product and load it.
    $product_id = $selected_value[$delta]['product_id'];

    // Check if selected product is an array.
    if (is_array($product_id)) {
      foreach ($product_id as $key => $value) {
        $product_id = $value['target_id'];
      }
    }

    $product = Product::load($product_id);
    // Get product variations to set them as options.
    $variations = $product->getVariationIds();
    // Get product variation options.
    $options = self::getVariationOptions($variations);

    // Set available options.
    $element['variation_ids']['#options'] = $options;
    $element['variation_ids']['#prefix'] = '<div id="product-variation--wrapper-' . $delta . '" class="show">';
    // Reset default value on product change.
    $element['variation_ids']['#default_value'] = [];

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand("#$wrapper_id", $element['variation_ids']));

    return $response;
  }

  /**
   * Ajax callback.
   *
   * Dummy callback so we do trigger field save on variations change.
   */
  public static function ajaxSetProductVariations(array $form, FormStateInterface $form_state) {
    return new AjaxResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['variation_ids'];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if (is_array($value['variation_ids'])) {
        $values[$key]['variation_ids'] = $value['variation_ids'];
      }
      if (isset($value['product_id'])) {
        $values[$key]['product_id'] = $value['product_id'];
        // Check if selected product is an array.
        if (is_array($value['product_id'])) {
          foreach ($value['product_id'] as $product_key => $product_value) {
            $values[$key]['product_id'] = $product_value['target_id'];
          }
        }
      }
    }

    return $values;
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Code reused from https://www.drupal.org/project/drupal/issues/1038316
   * @author - patch: https://www.drupal.org/files/issues/2019-02-07/1038316-188.patch (Thanks!)
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $max = $field_state['items_count'] - 1;
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));
    $id_prefix = implode('-', array_merge($parents, [$field_name]));
    $wrapper_id = Html::cleanCssIdentifier($id_prefix . '-add-wrapper');

    $elements = [];

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // Set custom weight handling - use value from user input.
          $input = $form_state->getUserInput();
          $weight = isset($input[$field_name][$delta]['_weight']) ? $input[$field_name][$delta]['_weight'] : $delta;
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            '#delta' => $max,
            '#value' => $weight,
            '#weight' => 100,
          ];
          $element['actions'] = [
            '#type' => 'actions',
            'remove_button' => [
              '#delta' => $delta,
              '#name' => implode('_', $element['#field_parents']) . "_remove_button_$delta",
              '#type' => 'submit',
              '#value' => t('Remove'),
              '#validate' => [],
              '#submit' => [[static::class, 'submitRemove']],
              '#limit_validation_errors' => [],
              '#attributes' => [
                'class' => ['remove-field-delta--' . $delta],
              ],
              '#ajax' => [
                'callback' => [static::class, 'removeAjaxContentRefresh'],
                'wrapper' => $wrapper_id,
                'effect' => 'fade',
              ],
            ],
          ];
        }

        $elements[$delta] = $element;
      }
    }

    $elements += [
      '#theme' => 'field_multiple_value_form',
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $title,
      '#description' => $description,
      '#max_delta' => $max,
    ];

    // Add 'add more' button, if not working with a programmed form.
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {

      $form['#wrapper_id'] = $wrapper_id;
      $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
      $elements['#suffix'] = '</div>';

      $elements['add_more'] = [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_more',
        '#value' => $delta > 0 ? t('Add another item') : t('Add item'),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'addMoreAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];

    }

    return $elements;
  }

  /**
   * Ajax submit callback for the "Remove" button.
   *
   * This re-numbers form elements and removes an item.
   *
   * Code reused from https://www.drupal.org/project/drupal/issues/1038316
   * @author - patch: https://www.drupal.org/files/issues/2019-02-07/1038316-188.patch (Thanks!)
   */
  public static function submitRemove(&$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $delta = $button['#delta'];
    $array_parents = array_slice($button['#array_parents'], 0, -4);
    $old_parents = array_slice($button['#parents'], 0, -3);
    $parent_element = NestedArray::getValue($form, array_merge($array_parents, ['widget']));
    $field_name = $parent_element['#field_name'];
    $parents = $parent_element['#field_parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    for ($i = $delta; $i < $field_state['items_count']; $i++) {
      $old_element_widget_parents = array_merge($array_parents, ['widget', $i + 1]);
      $old_element_parents = array_merge($old_parents, [$i + 1]);
      $new_element_parents = array_merge($old_parents, [$i]);
      $moving_element = NestedArray::getValue($form, $old_element_widget_parents);
      $moving_element_input = NestedArray::getValue($form_state->getUserInput(), $old_element_parents);

      // Tell the element where it's being moved to.
      $moving_element['#parents'] = $new_element_parents;

      // Move the element around.
      $user_input = $form_state->getUserInput();
      NestedArray::setValue($user_input, $moving_element['#parents'], $moving_element_input);
      $user_input[$field_name] = array_filter(NestedArray::getValue($user_input, $old_parents));

      $form_state->setUserInput($user_input);
    }
    unset($parent_element[$delta]);
    NestedArray::setValue($form, $array_parents, $parent_element);

    if ($field_state['items_count'] > 0) {
      $field_state['items_count']--;
    }
    $key_exists = '';
    unset($array_parents[1]);
    $input = NestedArray::getValue($form_state->getUserInput(), $array_parents, $key_exists);

    $weight = -1 * $field_state['items_count'];
    foreach ($input as $key => $item) {
      if ($item) {
        $input[$key]['_weight'] = $weight++;
      }
    }
    $user_input = $form_state->getUserInput();
    NestedArray::setValue($user_input, $array_parents, $input);
    $form_state->setUserInput($user_input);
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax refresh callback for the "Remove" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   *
   * Code reused from https://www.drupal.org/project/drupal/issues/1038316
   * @author - patch: https://www.drupal.org/files/issues/2019-02-07/1038316-188.patch (Thanks!)
   */
  public static function removeAjaxContentRefresh(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

}
