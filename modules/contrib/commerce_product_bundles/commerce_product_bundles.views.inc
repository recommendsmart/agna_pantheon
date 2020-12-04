<?php

/**
 * Implements hook_views_data().
 */
function commerce_product_bundles_views_data() {
  $data['commerce_order_item']['cart_bundle_image'] = [
    'title' => t('Cart Item Bundle Image'),
    'help' => t('Display bundle image.'),
    'field' => [
      'title' => t('Cart Item Bundle Image'),
      'entity field' => 'field_bundle_image',
      'id' => 'cart_bundle_image',
    ],
  ];

  return $data;
}

