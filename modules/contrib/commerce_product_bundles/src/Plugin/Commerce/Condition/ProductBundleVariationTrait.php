<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariation;

/**
 * Provides common configuration for the product bundle conditions.
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\Plugin\Commerce\Condition\VariationTypeTrait
 */
trait ProductBundleVariationTrait {

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
      'bundle_variations' => [],
      'negate' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $products_bundle_variation = NULL;
    $product_variation_ids = $this->getProductBundleVariationIds();
    if (!empty($product_variation_ids)) {
      $products_bundle_variation = ProductBundleVariation::loadMultiple($product_variation_ids);
    }
    $form['bundle_variations'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product bundle variation'),
      '#default_value' => $products_bundle_variation,
      '#target_type' => 'commerce_bundle_variation',
      '#tags' => TRUE,
      '#maxlength' => 20000,
      '#required' => TRUE,
      '#selection_handler' => 'default:commerce_product'
    ];

    $form['negate'] = [
      '#prefix' => '<strong>' . $this->t('Behavior') . '</strong>',
      '#type' => 'checkbox',
      '#title' => $this->t('Negate'),
      '#default_value' => isset($this->configuration['negate']) ? $this->configuration['negate'] : 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Convert selected IDs into UUIDs, and store them.
    $values = $form_state->getValue($form['#parents']);
    $product_bundle_ids = array_column($values['bundle_variations'], 'target_id');
    $product_bundle_uuids = $this->entityUuidMapper->mapFromIds('commerce_bundle_variation', $product_bundle_ids);

    // Set variations.
    $this->configuration['bundle_variations'] = [];
    foreach ($product_bundle_uuids as $uuid) {
      $this->configuration['bundle_variations'][] = [
        'bundle_variation' => $uuid,
      ];
    }

    // Set behavior.
    $this->configuration['negate'] = isset($values['negate']) ? $values['negate'] : 0;

  }

  /**
   * Gets the configured product bundle IDs.
   *
   * @return array
   *   The product bundle IDs.
   */
  protected function getProductBundleVariationIds() {
    // Map the UUIDs.
    $product_uuids = array_column($this->configuration['bundle_variations'], 'bundle_variation');
    return $this->entityUuidMapper->mapToIds('commerce_bundle_variation', $product_uuids);
  }

}
