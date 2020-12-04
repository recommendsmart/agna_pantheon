<?php

namespace Drupal\commerce_product_bundles\Plugin\views\field;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class CartBundleImage
 *
 * @package Drupal\commerce_product_bundles\Plugin\views\field
 * @ingroup views_field_handlers
 *
 * @ViewsField("cart_bundle_image")
 */
class CartBundleImage extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @param \Drupal\views\ResultRow $values
   *
   * @return \Drupal\Component\Render\MarkupInterface|\Drupal\views\Render\ViewsRenderPipelineMarkup|mixed|string
   */
  public function render(ResultRow $values) {
    $elements = [];
    $images_values = [];
    $images= [];
    $default_fallback = [];
    /** @var \Drupal\views\ResultRow $current_row */
    $current_row  = $this->view->result[$this->view->row_index];
    $order_item = $current_row->_relationship_entities['order_items'];
    $bundle_variation = $order_item->getPurchasedEntity();

    if($bundle_variation instanceof ProductBundleVariationInterface && $order_item instanceof OrderItem && $bundle_variation->hasField('field_bundle_image')) {
      $default_value = [];
      $ref_variations = $order_item->get('field_product_variation_ref')->getValue();
      foreach ($ref_variations as $key => $ref_variation) {
        $default_value[] = $ref_variation['product_var_id'];
      }
      $images = $bundle_variation->get('field_bundle_image')->view(['default']);
      $images_values = $images['#items']->getValue();
      // Define fallback image element.
      $default_fallback[] = reset($images_values);
      foreach ($images_values as $key => $image) {
        $image_product_combo = $image['product_combo'];
        $match = array_diff($default_value, $image_product_combo);
        // Show only matching image.
        if(!empty($match)) {
          unset($images_values[$key]);
        }
      }
    }

    if(empty($images_values)) {
      $images_values = $default_fallback;
    }

    if(!empty($images_values) && isset($images[key($images_values)])) {
      $elements = $images[key($images_values)];
    }

    return $elements;
  }

}
