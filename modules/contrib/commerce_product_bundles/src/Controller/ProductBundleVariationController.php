<?php

namespace Drupal\commerce_product_bundles\Controller;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides title callbacks for Product Bundle Variation routes.
 *
 * Code was taken and modified from:
 * @see \Drupal\commerce_product\Controller\ProductVariationController
 */
class ProductBundleVariationController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * ProductBundleVariationController constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(EntityRepositoryInterface $entity_repository, TranslationInterface $string_translation) {
    $this->entityRepository = $entity_repository;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('string_translation')
    );
  }

  /**
   * Provides the add title callback for Product Bundle Variations.
   *
   * @return string
   *   The title for the Product Bundle Variation add page.
   */
  public function addTitle() {
    return $this->t('Add bundle variation');
  }

  /**
   * Provides the edit title callback for Product Bundle Variations.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The title for the Product Bundle Variation edit page.
   */
  public function editTitle(RouteMatchInterface $route_match) {
    $product_bundle_variation = $route_match->getParameter('commerce_bundle_variation');
    $product_bundle_variation = $this->entityRepository->getTranslationFromContext($product_bundle_variation);

    return $this->t('Edit %label', ['%label' => $product_bundle_variation->label()]);
  }

  /**
   * Provides the delete title callback for Product Bundle Variations.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The title for the Product Bundle Variation delete page.
   */
  public function deleteTitle(RouteMatchInterface $route_match) {
    $product_bundle_variation = $route_match->getParameter('commerce_bundle_variation');
    $product_bundle_variation = $this->entityRepository->getTranslationFromContext($product_bundle_variation);

    return $this->t('Delete %label', ['%label' => $product_bundle_variation->label()]);
  }

  /**
   * Provides the collection title callback for Product Bundle Variations.
   *
   * @return string
   *   The title for the  Product Bundle Variation collection.
   */
  public function collectionTitle() {
    // Note that ProductVariationListBuilder::getForm() overrides the page
    // title. The title defined here is used only for the breadcrumb.
    return $this->t('Bundle Variations');
  }

}
