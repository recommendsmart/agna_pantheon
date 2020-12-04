<?php

namespace Drupal\commerce_product_bundles\Service;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;

/**
 * Class ProductBundleVariationFieldRenderer
 *
 * @package Drupal\commerce_product_bundles\Service
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\ProductVariationFieldRenderer
 */
class ProductBundleVariationFieldRenderer implements ProductBundleVariationFieldRendererInterface {

  /**
   * The product bundle variation view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $bundleVariationViewBuilder;

  /**
   * ProductBundleVariationFieldRenderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->bundleVariationViewBuilder = $entity_type_manager->getViewBuilder('commerce_bundle_variation');
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\commerce_product\ProductVariationFieldRenderer::renderField()
   */
  public function renderFields(ProductBundleVariationInterface $bundle_variation, $view_mode = 'default') {
    $build = $this->bundleVariationViewBuilder->view($bundle_variation, $view_mode);

    // Formatters aren't called until #pre_render.
    foreach ($build['#pre_render'] as $callable) {
      $build = call_user_func($callable, $build);
    }
    unset($build['#pre_render']);
    // Rendering the product can cause an infinite loop.
    unset($build['product_bundle_id']);
    // Fields are rendered individually, top-level properties are not needed.
    foreach (array_keys($build) as $key) {
      if (Element::property($key)) {
        unset($build[$key]);
      }
    }
    // Prepare the fields for AJAX replacement.
    foreach ($build as $field_name => $rendered_field) {
      $build[$field_name] = $this->prepareForAjax($rendered_field, $field_name, $bundle_variation);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\commerce_product\ProductVariationFieldRenderer::renderField()
   */
  public function renderField($field_name, ProductBundleVariationInterface $bundle_variation, $display_options = []) {
    $rendered_field = $this->bundleVariationViewBuilder->viewField($bundle_variation->get($field_name), $display_options);
    // An empty array indicates that the field is hidden on the view display.
    if (!empty($rendered_field)) {
      $rendered_field = $this->prepareForAjax($rendered_field, $field_name, $bundle_variation);
    }

    return $rendered_field;
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\commerce_product\ProductVariationFieldRenderer::replaceRenderedFields()
   */
  public function replaceRenderedFields(AjaxResponse $response, ProductBundleVariationInterface $bundle_variation, $view_mode = 'default') {
    $rendered_fields = $this->renderFields($bundle_variation, $view_mode);
    foreach ($rendered_fields as $field_name => $rendered_field) {
      $response->addCommand(new ReplaceCommand('.' . $rendered_field['#ajax_replace_class'], $rendered_field));
    }
  }

  /**
   * Prepares the rendered field for AJAX replacement.
   *
   * @param array $rendered_field
   *   The rendered field.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The product variation.
   *
   * @return array
   *   The prepared rendered field.
   *
   * @see \Drupal\commerce_product\ProductVariationFieldRenderer::prepareForAjax()
   */
  protected function prepareForAjax(array $rendered_field, $field_name, ProductBundleVariationInterface $bundle_variation) {
    $ajax_class = $this->buildAjaxReplacementClass($field_name, $bundle_variation);
    $rendered_field['#attributes']['class'][] = $ajax_class;
    $rendered_field['#ajax_replace_class'] = $ajax_class;
    // Ensure that a <div> is rendered even if the field is empty, to allow
    // field replacement to work when the variation changes.
    if (!Element::children($rendered_field)) {
      $rendered_field['#type'] = 'container';
    }

    return $rendered_field;
  }

  /**
   * Builds the AJAX replacement CSS class for a bundle variation's field.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The product variation.
   *
   * @return string
   *   The CSS class.
   */
  protected function buildAjaxReplacementClass($field_name, ProductBundleVariationInterface $bundle_variation) {
    // Use field mapping to add fields to ajax replace.
    if (!empty($this->overrideFieldMapping($field_name, $bundle_variation->getBundleProductId()))) {
      return $this->overrideFieldMapping($field_name, $bundle_variation->getBundleProductId());
    }
    return 'product-bundles--variation-field--variation_'. $field_name . '__' . $bundle_variation->getBundleProductId();
  }

  /**
   * Mapping for returning clean classes.
   *
   * @param string $field_name
   *   Machine field name.
   *
   * @return bool|mixed
   *   Return class or FALSE if mapping does not exist.
   */
  public function overrideFieldMapping($field_name, $bundle_product_id) {
    $mapping = [
      'price' => 'field--name-unit-price .field--widget-bundle-variation-price',
    ];

    if (isset($mapping[$field_name])) {
      return $mapping[$field_name];
    }

    return FALSE;
  }

}
