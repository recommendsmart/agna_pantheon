<?php

namespace Drupal\commerce_product_bundles;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\commerce_product_bundles\service\ProductBundleVariationFieldRendererInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProductBundleViewBuilder
 *
 * @package Drupal\commerce_product_bundles
 */
class ProductBundleViewBuilder extends EntityViewBuilder {

  /**
   * The product bundle field variation renderer.
   *
   * @var \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldRenderer
   */
  protected $bundleVariationFieldRenderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ProductBundleViewBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityRepository $entity_repository
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Theme\Registry $theme_registry
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_product_bundles\service\ProductBundleVariationFieldRendererInterface $variation_field_renderer
   */
  public function __construct(EntityTypeInterface $entity_type, EntityRepository $entity_repository, LanguageManagerInterface $language_manager,
                              Registry $theme_registry, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager,
                              ProductBundleVariationFieldRendererInterface $variation_field_renderer) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry, $entity_display_repository);

    $this->bundleVariationFieldRenderer = $variation_field_renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('commerce_product_bundles.bundle_variation_field_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $product_bundle_type_storage = $this->entityManager->getStorage('commerce_product_bundles_type');

    /** @var \Drupal\commerce_product_bundles\ProductBundleVariationStorageInterface $bundle_variation_storage */
    $bundle_variation_storage = $this->entityManager->getStorage('commerce_bundle_variation');

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $product_bundle_type */
    $product_bundle_type = $product_bundle_type_storage->load($entity->bundle());

    if ($product_bundle_type->shouldInjectBundleVariationFields() && $entity->getDefaultVariation()) {
      $bundle_variation = $bundle_variation_storage->loadFromContext($entity);
      $bundle_variation = $this->entityRepository->getTranslationFromContext($bundle_variation, $entity->language()->getId());

      $rendered_fields = $this->bundleVariationFieldRenderer->renderFields($bundle_variation, $view_mode);
      foreach ($rendered_fields as $field_name => $rendered_field) {
        $build['variation_' . $field_name] = $rendered_field;
      }
    }
  }

}
