<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldWidget;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'bundle_image_image' widget.
 *
 * @FieldWidget(
 *   id = "bundle_image_image",
 *   label = @Translation("Bundle Image"),
 *   field_types = {
 *     "bundle_image"
 *   }
 * )
 */
class BundleImageWidget extends ImageWidget {

  protected $entityTypeManager;

  /**
   * BundleImageWidget constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings,
                              ElementInfoManagerInterface $element_info, ImageFactory $image_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info, $image_factory);

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
      $container->get('element_info'),
      $container->get('image.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Form API callback: Processes a bundle_image_image field element.
   *
   * Expands the image_image type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];

    // Get default options for product variations select.
    $product_variations = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->getQuery()
      ->accessCheck(FALSE)
      ->condition('default_langcode', 1);

    $variations = $product_variations->execute();
    $options = [];
    foreach ($variations as $variation_id){
      $product_variation_eck = ProductVariation::load($variation_id);
      $options[$variation_id] = $product_variation_eck->label();
    }

    $element['product_combo'] = [
      '#type' => 'select2',
      '#options' => $options,
      '#target_type' => 'commerce_product_variation',
      '#title' => t('Product Variations Combo'),
      '#default_value' => isset($item['product_combo']) ? $item['product_combo'] : [],
      '#multiple' => TRUE,
      '#access' => (bool) $item['fids'],
      '#required' => TRUE,
      '#autocomplete' => TRUE,
      '#delta' => $element['#delta'],
      '#select2' => [],
      '#element_validate' => [[get_called_class(), 'validateRequiredLimitFields']],
    ];

    return parent::process($element, $form_state, $form);
  }

  /**
   * Validate callback for product_combo, alt and title field, if the user wants them required.
   *
   * This is separated in a validate function instead of a #required flag to
   * avoid being validated on the process callback.
   */
  public static function validateRequiredLimitFields($element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Only do validation if the function is triggered from other places than
    // the image process form.
    if (!empty($triggering_element['#submit']) && in_array('file_managed_file_submit', $triggering_element['#submit'], TRUE)) {
      $form_state->setLimitValidationErrors([]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Since file upload widget now supports uploads of more than one file at a
    // time it always returns an array of fids. We have to translate this to a
    // single fid, as field expects single value.
    $new_values = [];
    foreach ($values as $key => &$value) {
      foreach ($value['fids'] as $fid) {
        $new_value = $value;
        $new_value['target_id'] = $fid;
        unset($new_value['fids']);
        $new_values[] = $new_value;
      }
      if (is_array($value['product_combo'])) {
        $new_values[$key]['product_combo'] = $value['product_combo'];
      }
    }

    return $new_values;
  }

}
