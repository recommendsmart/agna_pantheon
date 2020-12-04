<?php

namespace Drupal\commerce_product_bundles;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_product_bundles\Event\FilterBundleVariationsEvent;
use Drupal\commerce_product_bundles\Event\ProductBundleEvents;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ProductBundleVariationStorage
 * Defines the product variation storage.
 *
 * @package Drupal\commerce_product_bundles
 */
class ProductBundleVariationStorage extends CommerceContentEntityStorage implements ProductBundleVariationStorageInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * ProductBundleVariationStorage constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager,
                              CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager,
                              EventDispatcherInterface $event_dispatcher, RequestStack $request_stack) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager,
      $event_dispatcher);

    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromContext(ProductBundleInterface $product_bundle) {
    $current_request = $this->requestStack->getCurrentRequest();
    if ($bundle_variation_id = $current_request->query->get('v')) {
      if (in_array($bundle_variation_id, $product_bundle->getVariationIds())) {
        /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation */
        $bundle_variation = $this->load($bundle_variation_id);
        if ($bundle_variation->isPublished() && $bundle_variation->access('view')) {
          return $bundle_variation;
        }
      }
    }
    return $product_bundle->getDefaultVariation();
  }

  /**
   * {@inheritdoc}
   */
  public function loadEnabled(ProductBundleInterface $product_bundle) {
    $ids = [];
    foreach ($product_bundle->bundle_variations as $variation) {
      $ids[$variation->target_id] = $variation->target_id;
    }
    // Speed up loading by filtering out the IDs of disabled variations.
    $query = $this->getQuery()
      ->addTag('entity_access')
      ->condition('status', TRUE)
      ->condition('bundle_variation_id', $ids, 'IN');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    // Restore the original sort order.
    $result = array_intersect_key($ids, $result);

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $enabled_bundle_variations */
    $enabled_bundle_variations = $this->loadMultiple($result);

    // Allow modules to apply own filtering.
    $event = new FilterBundleVariationsEvent($product_bundle, $enabled_bundle_variations);
    $this->eventDispatcher->dispatch(ProductBundleEvents::FILTER_BUNDLE_VARIATIONS, $event);
    $enabled_bundle_variations = $event->getVariations();

    // Filter out bundle variations that can't be accessed.
    foreach ($enabled_bundle_variations as $bundle_variation_id => $enabled_bundle_variation) {
      if (!$enabled_bundle_variation->access('view')) {
        unset($enabled_bundle_variations[$bundle_variation_id]);
      }
    }

    return $enabled_bundle_variations;
  }

}
