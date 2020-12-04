<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariation;
use Drupal\commerce_product_bundles\Event\ProductBundleEvents;
use Drupal\commerce_product_bundles\Event\ProductBundleVariationAjaxChangeEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProductBundleVariationWidgetBase
 *
 * Provides the base structure for product bundle variation widgets.
 *
 * @package Drupal\commerce_product_bundles\Plugin\Field\FieldWidget
 *
 * Code was taken form and modified:
 * @see \Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationWidgetBase
 */
abstract class ProductBundleVariationWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The product bundle variation storage.
   *
   * @var \Drupal\commerce_product_bundles\ProductBundleVariationStorageInterface
   */
  protected $bundleVariationStorage;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * ProductBundleVariationWidgetBase constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityRepository = $entity_repository;
    $this->bundleVariationStorage = $entity_type_manager->getStorage('commerce_bundle_variation');
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
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order_item' && $field_name == 'purchased_entity';
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Assumes that the variation ID comes from an $element['bundle_variation'] built
    // in formElement().
    foreach ($values as $key => $value) {
      $values[$key] = [
        'target_id' => $value['bundle_variation'],
      ];
    }

    return $values;
  }

  /**
   * #ajax callback: Replaces the rendered fields on bundle variation change.
   *
   * Assumes the existence of a 'selected_bundle_variation' in $form_state.
   *
   *  @see \Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationWidgetBase::ajaxRefresh()
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Render\MainContent\MainContentRendererInterface $ajax_renderer */
    $ajax_renderer = \Drupal::service('main_content_renderer.ajax');
    $request = \Drupal::request();
    $route_match = \Drupal::service('current_route_match');
    /** @var \Drupal\Core\Ajax\AjaxResponse $response */
    $response = $ajax_renderer->renderResponse($form, $request, $route_match);

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $selected_variation */
    $selected_variation = ProductBundleVariation::load($form_state->get('selected_bundle_variation'));

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle */
    $product_bundle = $form_state->get('product_bundle');
    if ($selected_variation->hasTranslation($product_bundle->language()->getId())) {
      $selected_variation = $selected_variation->getTranslation($product_bundle->language()->getId());
    }

    /** @var \Drupal\commerce_product_bundles\service\ProductBundleVariationFieldRendererInterface $variation_field_renderer */
    $variation_field_renderer = \Drupal::service('commerce_product_bundles.bundle_variation_field_renderer');
    $view_mode = $form_state->get('view_mode');
    $variation_field_renderer->replaceRenderedFields($response, $selected_variation, $view_mode);

    $event = new ProductBundleVariationAjaxChangeEvent($selected_variation, $response, $view_mode);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(ProductBundleEvents::PRODUCT_BUNDLE_VARIATION_AJAX_CHANGE, $event);

    return $response;
  }

  /**
   * Gets the default bundle variation for the widget.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle
   * @param array $bundle_variations
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   */
  protected function getDefaultBundleVariation(ProductBundleInterface $product_bundle, array $bundle_variations) {
    $langcode = $product_bundle->language()->getId();
    $selected_variation = $this->bundleVariationStorage->loadFromContext($product_bundle);
    $selected_variation = $this->entityRepository->getTranslationFromContext($selected_variation, $langcode);
    // The returned variation must also be enabled.
    if (!in_array($selected_variation, $bundle_variations)) {
      $selected_variation = reset($variations);
    }
    return $selected_variation;
  }

  /**
   * Gets the enabled variations for the product.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleInterface $product_bundle
   *   The product bundle.
   *
   * @return \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface[]
   *   An array of bundle variations.
   */
  protected function loadEnabledVariations(ProductBundleInterface $product_bundle) {
    $langcode = $product_bundle->language()->getId();
    $variations = $this->bundleVariationStorage->loadEnabled($product_bundle);
    foreach ($variations as $key => $variation) {
      $variations[$key] = $this->entityRepository->getTranslationFromContext($variation, $langcode);
    }
    return $variations;
  }

}
