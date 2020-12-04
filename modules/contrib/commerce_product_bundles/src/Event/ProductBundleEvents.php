<?php

namespace Drupal\commerce_product_bundles\Event;

final class ProductBundleEvents {

  /**
   * Name of the event fired after loading a product bundle.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleEvent
   */
  const PRODUCT_BUNDLE_LOAD = 'commerce_product_bundles.commerce_product_bundles.load';

  /**
   * Name of the event fired after creating a new product bundle.
   *
   * Fired before the product bundle is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleEvent
   */
  const PRODUCT_BUNDLE_CREATE = 'commerce_product_bundles.commerce_product_bundles.create';

  /**
   * Name of the event fired before saving a product bundle.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleEvent
   */
  const PRODUCT_BUNDLE_PRESAVE = 'commerce_product_bundles.commerce_product_bundles.presave';

  /**
   * Name of the event fired after saving a new product bundle.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleEvent
   */
  const PRODUCT_BUNDLE_INSERT = 'commerce_product_bundles.commerce_product_bundles.insert';

  /**
   * Name of the event fired after saving an existing product bundle.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleEvent
   */
  const PRODUCT_BUNDLE_UPDATE = 'commerce_product_bundles.commerce_product_bundles.update';

  /**
   * Name of the event fired before deleting a product bundle.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleEvent
   */
  const PRODUCT_BUNDLE_PREDELETE = 'commerce_product_bundles.commerce_product_bundles.predelete';

  /**
   * Name of the event fired after deleting a product bundle.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleEvent
   */
  const PRODUCT_BUNDLE_DELETE = 'commerce_product_bundles.commerce_product_bundles.delete';

  /**
   * Name of the event fired after changing the product bundle variation via ajax.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationAjaxChangeEvent
   */
  const PRODUCT_BUNDLE_VARIATION_AJAX_CHANGE = 'commerce_product_bundles.commerce_bundle_variation.ajax_change';

  /**
   * Name of the event fired after loading a product bundle variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationEvent
   */
  const PRODUCT_BUNDLE_VARIATION_LOAD = 'commerce_product_bundles.commerce_bundle_variation.load';

  /**
   * Name of the event fired after creating a new product bundle variation.
   *
   * Fired before the product bundle variation is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationEvent
   */
  const PRODUCT_BUNDLE_VARIATION_CREATE = 'commerce_product_bundles.commerce_bundle_variation.create';

  /**
   * Name of the event fired before saving a product bundle variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationEvent
   */
  const PRODUCT_BUNDLE_VARIATION_PRESAVE = 'commerce_product_bundles.commerce_bundle_variation.presave';

  /**
   * Name of the event fired after saving a new product bundle variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationEvent
   */
  const PRODUCT_BUNDLE_VARIATION_INSERT = 'commerce_product_bundles.commerce_bundle_variation.insert';

  /**
   * Name of the event fired after saving an existing product bundle variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationEvent
   */
  const PRODUCT_BUNDLE_VARIATION_UPDATE = 'commerce_product_bundles.commerce_bundle_variation.update';

  /**
   * Name of the event fired before deleting a product bundle variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationEvent
   */
  const PRODUCT_BUNDLE_VARIATION_PREDELETE = 'commerce_product_bundles.commerce_bundle_variation.predelete';

  /**
   * Name of the event fired after deleting a product bundle variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\ProductBundleVariationEvent
   */
  const PRODUCT_BUNDLE_VARIATION_DELETE = 'commerce_product_bundles.commerce_bundle_variation.delete';

  /**
   * Name of the event fired when filtering variations.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_bundles\Event\FilterBundleVariationsEvent
   */
  const FILTER_BUNDLE_VARIATIONS = 'commerce_product_bundles.filter_bundle_variations';
}
