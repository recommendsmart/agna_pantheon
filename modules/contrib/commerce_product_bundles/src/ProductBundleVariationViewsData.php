<?php

namespace Drupal\commerce_product_bundles;

/**
 * Class ProductBundleVariationViewsData
 *
 * @package Drupal\commerce_product_bundles
 */
class ProductBundleVariationViewsData extends CommerceBundleEntityViewsData {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce_product\ProductVariationViewsData
   */
  protected function addEntityLinks(array &$data) {
    parent::addEntityLinks($data);

    $t_arguments = ['@entity_type_label' => $this->entityType->getLabel()];
    $data['view_commerce_bundle_variation']['field'] = [
      'title' => $this->t('Link to @entity_type_label', $t_arguments),
      'help' => $this->t('Provide a view link to the @entity_type_label.', $t_arguments),
      'id' => 'commerce_bundle_variation_view_link',
    ];
  }

}
