<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides common configuration for the product bundle conditions.
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\Plugin\Commerce\Condition\ProductTrait
 */
trait ProductBundleTrait {

  /**
   * The product bundle storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productBundleStorage;

  /**
   * The entity UUID mapper.
   *
   * @var \Drupal\commerce\EntityUuidMapperInterface
   */
  protected $entityUuidMapper;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'product_bundles' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $product_bundles = NULL;
    $product_bundle_ids = $this->getProductBundleIds();
    if (!empty($product_bundle_ids)) {
      $product_bundles = $this->productBundleStorage->loadMultiple($product_bundle_ids);
    }
    $form['product_bundles'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product Bundles'),
      '#default_value' => $product_bundles,
      '#target_type' => 'commerce_product_bundles',
      '#tags' => TRUE,
      '#required' => TRUE,
      '#maxlength' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce_product\Plugin\Commerce\Condition\ProductTrait::submitConfigurationForm()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Convert selected IDs into UUIDs, and store them.
    $values = $form_state->getValue($form['#parents']);
    $product_bundle_ids = array_column($values['product_bundles'], 'target_id');
    $product_bundle_uuids = $this->entityUuidMapper->mapFromIds('commerce_product_bundles', $product_bundle_ids);
    $this->configuration['product_bundles'] = [];
    foreach ($product_bundle_uuids as $uuid) {
      $this->configuration['product_bundles'][] = [
        'product_bundle' => $uuid,
      ];
    }
  }

  /**
   * Gets the configured product bundle IDs.
   *
   * @return array
   *   The product IDs.
   *
   * @see \Drupal\commerce_product\Plugin\Commerce\Condition\ProductTrait::getProductIds()
   */
  protected function getProductBundleIds() {
    $product_ids = array_column($this->configuration['product_bundles'], 'product_bundle_id');
    if (!empty($product_ids)) {
      // Legacy configuration found, with explicit product IDs.
      return $product_ids;
    }
    else {
      // Map the UUIDs.
      $product_uuids = array_column($this->configuration['product_bundles'], 'product_bundle');
      return $this->entityUuidMapper->mapToIds('commerce_product_bundles', $product_uuids);
    }
  }

}
