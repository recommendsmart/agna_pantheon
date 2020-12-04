<?php

namespace Drupal\commerce_product_bundles;

use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\commerce_product_bundles\Controller\ProductBundleVariationController;
use Symfony\Component\Routing\Route;

/**
 * Class ProductBundleVariationRouteProvider
 * Provides routes for the product variation entity.
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\ProductVariationRouteProvider
 *
 * @package Drupal\commerce_product_bundles
 */
class ProductBundleVariationRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('add-form'));
    $route
      ->setDefaults([
        '_entity_form' => 'commerce_bundle_variation.add',
        'entity_type_id' => 'commerce_bundle_variation',
        '_title_callback' => ProductBundleVariationController::class . '::addTitle',
      ])
      ->setRequirement('_bundle_variation_create_access', 'TRUE')
      ->setOption('parameters', [
        'commerce_product_bundles' => [
          'type' => 'entity:commerce_product_bundles',
        ],
      ])
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getEditFormRoute($entity_type);
    $route->setDefault('_title_callback', ProductBundleVariationController::class . '::editTitle');
    $route->setOption('parameters', [
      'commerce_product_bundles' => [
        'type' => 'entity:commerce_product_bundles',
      ],
      'commerce_bundle_variation' => [
        'type' => 'entity:commerce_bundle_variation',
      ],
    ]);
    $route->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getDeleteFormRoute($entity_type);
    $route->setDefault('_title_callback', ProductBundleVariationController::class . '::deleteTitle');
    $route->setOption('parameters', [
      'commerce_product_bundles' => [
        'type' => 'entity:commerce_product_bundles',
      ],
      'commerce_bundle_variation' => [
        'type' => 'entity:commerce_bundle_variation',
      ],
    ]);
    $route->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('collection'));
    $route->addDefaults([
      '_entity_list' => 'commerce_bundle_variation',
      '_title_callback' => ProductBundleVariationController::class . '::collectionTitle',
    ])
      ->setRequirement('_bundle_variation_collection_access', 'TRUE')
      ->setOption('parameters', [
        'commerce_product_bundles' => [
          'type' => 'entity:commerce_product_bundles',
        ],
      ])
      ->setOption('_admin_route', TRUE);

    return $route;
  }

}
