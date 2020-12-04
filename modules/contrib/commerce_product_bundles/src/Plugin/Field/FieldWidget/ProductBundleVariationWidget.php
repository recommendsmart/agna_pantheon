<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldWidget;

use Drupal\commerce_product\ProductAttributeFieldManagerInterface;
use Drupal\commerce_product\ProductVariationAttributeMapperInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariation;
use Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_product_bundles_variation' widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_bundles_variation",
 *   label = @Translation("Product bundle variation"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ProductBundleVariationWidget extends ProductBundleVariationWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldRendererInterface
   */
  protected $bundleVariationFieldMapper;

  /**
   * The product attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The product variation attribute mapper.
   *
   * @var \Drupal\commerce_product\ProductVariationAttributeMapperInterface
   */
  protected $variationAttributeMapper;

  /**
   * ProductBundleVariationWidget constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   * @param \Drupal\commerce_product\ProductVariationAttributeMapperInterface $variation_attribute_mapper
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings,
                              EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository,
                              ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper, ProductAttributeFieldManagerInterface $attribute_field_manager, ProductVariationAttributeMapperInterface $variation_attribute_mapper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_type_manager, $entity_repository);

    $this->bundleVariationFieldMapper = $bundle_variation_field_mapper;
    $this->attributeFieldManager = $attribute_field_manager;
    $this->variationAttributeMapper = $variation_attribute_mapper;
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
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('commerce_product_bundles.bundle_variation_mapper'),
      $container->get('commerce_product.attribute_field_manager'),
      $container->get('commerce_product.variation_attribute_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle */
    $product_bundle = $form_state->get('product_bundle');
    $bundle_variations = $this->loadEnabledVariations($product_bundle);

    // Build the full variation form.
    $wrapper_id = Html::getUniqueId('commerce-product-add-to-cart-bundle-form');
    $form += [
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    // If an operation caused the form to rebuild, select the variation from
    // the user's current input.
    $selected_variation = NULL;

    if ($form_state->isRebuilding()) {
      $parents = array_merge($element['#field_parents'], [$items->getName(), $delta, 'bundle_variations_options']);
      $bundle_variation_id = (array) NestedArray::getValue($form_state->getUserInput(), $parents);
      if(!empty($bundle_variation_id)){
        $selected_variation = ProductBundleVariation::load(reset($bundle_variation_id));
      }
    }
    // Otherwise fallback to the default.
    if (!$selected_variation) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $items->getEntity();
      if ($order_item->isNew()) {
        $selected_variation = $this->getDefaultBundleVariation($product_bundle, $bundle_variations);
      }
      else {
        $selected_variation = $order_item->getPurchasedEntity();
      }
    }

    $element['bundle_variation'] = [
      '#type' => 'value',
      '#value' => $selected_variation->id(),
    ];

    // Set the selected variation in the form state for our AJAX callback.
    $form_state->set('selected_bundle_variation', $selected_variation->id());

    $element['bundle_variations_options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['bundle-variation-widgets'],
      ],
    ];

    $options = [];
    foreach ($bundle_variations as $variation_id => $bundle_variation) {
      $options[$variation_id] = $bundle_variation->label();
    }

    // Default bundle variation will be set as default.
    $bundle_variations_element = [
      '#type' => 'commerce_product_bundles_rendered',
      '#title' => $product_bundle->label(),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $selected_variation->id(),
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $form['#wrapper_id'],
        // Prevent a jump to the top of the page.
        'disable-refocus' => TRUE
      ],
    ];
    // Convert the _none option into #empty_value.
    if (isset($bundle_variations_element['#options']['_none'])) {
      if (!$bundle_variations_element['#required']) {
        $bundle_variations_element['#empty_value'] = '';
      }
      unset($bundle_variations_element['#options']['_none']);
    }
    if (empty($bundle_variations_element['#options'])) {
      $bundle_variations_element['#access'] = FALSE;
    }

    $element['bundle_variations_options'] = $bundle_variations_element;

    //// Referenced Variation widget /////
    // Generate referenced products widget.
    $element['bundle_variations_ref_options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'bundle-variation-select--' . $product_bundle->bundle()
        ],
      ]
    ];

    // Generate referenced products widget.
    $element['quantity'] = [
      '#type' => 'container'
    ];

    // Loop through all ref. variations and display widget.
    foreach ($this->bundleVariationFieldMapper->prepareBundleVariations($selected_variation) as $ref_product_id => $bundle_variation) {
      $default_value = $bundle_variation->getDefaultProductVariation(TRUE);
      $bundle_variation_element = [
        '#type' => 'bundle_ref_default_variations_rendered',
        '#theme_wrappers' => ['bundle_ref_default_select_wrapper'],
        '#title' => $bundle_variation->getLabel(),
        '#refItems' => $bundle_variation->getRefVariations(),
        '#quantity' => $bundle_variation->getQuantity(),
        '#required' => TRUE,
        '#attribute_title' => NULL,
        '#options' => [],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $form['#wrapper_id'],
          // Prevent a jump to the top of the page.
          'disable-refocus' => TRUE,
        ],
      ];

      $element['bundle_variations_ref_options'][$ref_product_id] = $bundle_variation_element;

      // Set quantity value.
      $element['quantity'][$ref_product_id] = [
        '#type' => 'hidden',
        '#value' => $bundle_variation->getQuantity()
      ];
      // Set ref. variations default value.
      $element['bundle_variations_ref_options'][$ref_product_id]['#default_value'] = $default_value;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle */
    $product_bundle = $form_state->get('product_bundle');
    $default_variation = $product_bundle->getDefaultVariation();

    foreach ($values as $key => &$value) {
      $bundle_variations = isset($value['bundle_variations_options']) ? $value['bundle_variations_options'] : [];
      if ($bundle_variations) {
        $value['bundle_variation'] = $bundle_variations;
      }
      else {
        $value['bundle_variation'] = $default_variation->id();
      }
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

}
