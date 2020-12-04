<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'bundle_image' formatter.
 *
 * @FieldFormatter(
 *   id = "bundle_image",
 *   label = @Translation("Bundle Image"),
 *   field_types = {
 *     "bundle_image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class BundleImageFormatter extends ImageFormatter {

  /**
   * Request stack service.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldRendererInterface
   */
  protected $bundleVariationFieldMapper;

  /**
   * BundleImageFormatter constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings,
                              AccountInterface $current_user, EntityStorageInterface $image_style_storage, RequestStack $request_stack,
                              ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
    $this->requestStack = $request_stack;
    $this->bundleVariationFieldMapper = $bundle_variation_field_mapper;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('request_stack'),
      $container->get('commerce_product_bundles.bundle_variation_mapper')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @TODO Switch image field to order item so we have access to 'purchased_entity'.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get parents render element array.
    $elements = parent::viewElements($items, $langcode);
    $default_fallback = [];

    if($elements) {

      // @TODO Not the best solution... Return to this (contrib patch is very welcome ;) )
      // Check if user made selection.
      $selected_vars = $this->requestStack->getCurrentRequest()->request->get('purchased_entity');

      // @TODO Fix this logic.
      // Get Triggering element name.
      $triggering_element = $this->requestStack->getCurrentRequest()->request->get('_triggering_element_name');
      $default_value = [];

      // If user made selection set default_value to selection.
      // If user changes 'bundle_variations_options' use default variation.
      if(isset($selected_vars[0]['bundle_variations_ref_options']) && $triggering_element !== 'purchased_entity[0][bundle_variations_options]') {
        $default_value = $selected_vars[0]['bundle_variations_ref_options'];
      }
      // If not load default variations.
      else {
        $default_bundle_variation = $items->getParent()->getEntity();
        foreach ($this->bundleVariationFieldMapper->prepareBundleVariations($default_bundle_variation) as $ref_product_id => $bundle_variation) {
          $default_value[$bundle_variation->getRefProduct(TRUE)] = $bundle_variation->getDefaultProductVariation(TRUE);
        }
      }

      // Define fallback image element.
      $default_fallback = reset($elements);
      foreach ($elements as $key => $element) {
        $item = $element['#item'];
        $image_product_combo = $item->getValue()['product_combo'];
        $match = array_diff($default_value, $image_product_combo);
        // Show only matching image.
        if(!empty($match)) {
          unset($elements[$key]);
        }
      }
    }

    return $elements ? $elements : $default_fallback;
  }

}
