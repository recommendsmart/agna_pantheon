<?php

namespace Drupal\commerce_product_bundles\ContextProvider;

use Drupal\commerce_product_bundles\Entity\ProductBundle;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current Product Bundle as context on commerce_product_bundles routes.
 *
 * Code was taken and modified from:
 * @see \Drupal\commerce_product\ContextProvider\ProductRouteContext
 */
class ProductBundleRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * ProductBundleRouteContext constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = new ContextDefinition('entity:commerce_product_bundles', NULL, FALSE);
    $value = NULL;
    if ($product_bundle = $this->routeMatch->getParameter('commerce_product_bundles')) {
      $value = $product_bundle;
    }
    elseif ($this->routeMatch->getRouteName() == 'entity.commerce_product_bundles.add_form') {
      $product_bundle_type = $this->routeMatch->getParameter('commerce_product_bundles_type');
      $value = ProductBundle::create(['type' => $product_bundle_type->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);

    return ['commerce_product_bundles' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition(
      'entity:commerce_product_bundles', $this->t('Product Bundle from URL')
    ));
    return ['commerce_product_bundles' => $context];
  }

}
