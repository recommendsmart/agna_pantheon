<?php

namespace Drupal\commerce_product_bundles;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\commerce_product_bundles\Entity\ProductBundleType;

/**
 * Class ProductBundleListBuilder
 *
 * @package Drupal\commerce_product_bundles
 *
 * List builder for product bundels.
 */
class ProductBundleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['type'] = t('Bundle Type');
    $header['status'] = t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundle $entity */
    $product_bundle_type = ProductBundleType::load($entity->bundle());

    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $entity->toUrl(),
    ];
    $row['type'] = $product_bundle_type->label();
    $row['status'] = $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $variations_url = new Url('entity.commerce_bundle_variation.collection', [
      'commerce_product_bundles' => $entity->id(),
    ]);
    if ($variations_url->access()) {
      $operations['variations'] = [
        'title' => $this->t('Bundle Variations'),
        'weight' => 20,
        'url' => $variations_url,
        'query' => ['destination' => NULL],
      ];
    }

    return $operations;
  }

}
