<?php

namespace Drupal\commerce_product_bundles\Event;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product bundle variation ajax change event.
 *
 * @see \Drupal\commerce_product\Event\ProductEvents
 */
class ProductBundleVariationAjaxChangeEvent extends Event {

  /**
   * The product bundle variation.
   *
   * @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface
   */
  protected $productBundleVariation;

  /**
   * The ajax response.
   *
   * @var \Drupal\Core\Ajax\AjaxResponse
   */
  protected $response;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * ProductBundleVariationAjaxChangeEvent constructor.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $product_bundle_variation
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   * @param string $view_mode
   */
  public function __construct(ProductBundleVariationInterface $product_bundle_variation, AjaxResponse $response, $view_mode = 'default') {
    $this->productBundleVariation = $product_bundle_variation;
    $this->response = $response;
    $this->viewMode = $view_mode;
  }

  /**
   * The product bundle variation.
   *
   * @return \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface
   *   The product bundle variation.
   */
  public function getProductBundleVariation() {
    return $this->productBundleVariation;
  }

  /**
   * The ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax reponse.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * The view mode used to render the product bundle variation.
   *
   * @return string
   *   The view mode.
   */
  public function getViewMode() {
    return $this->viewMode;
  }

}
