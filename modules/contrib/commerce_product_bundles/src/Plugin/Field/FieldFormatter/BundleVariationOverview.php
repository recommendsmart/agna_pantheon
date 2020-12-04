<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use Drupal\commerce_order\PriceCalculatorResult;
use Drupal\commerce_product_bundles\Service\ProductBundleVariationServiceInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'product_bundle_overview' formatter.
 *
 * @FieldFormatter(
 *   id = "product_bundle_overview",
 *   label = @Translation("Product Bundle overview"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class BundleVariationOverview extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity|EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * @var \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldRendererInterface
   */
  protected $bundleVariationFieldMapper;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Commerce Product Bundle Service.
   *
   * @var \Drupal\commerce_product_bundles\Service\ProductBundleVariationServiceInterface
   */
  protected $commerceBundleVariationService;

  /**
   * BundleVariationOverview constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\commerce_product_bundles\Service\ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\commerce_product_bundles\Service\ProductBundleVariationServiceInterface $bundle_variation_service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings,
                              EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository,
                              ProductBundleVariationFieldManagerInterface $bundle_variation_field_mapper, RendererInterface $renderer, ProductBundleVariationServiceInterface $bundle_variation_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->bundleVariationFieldMapper = $bundle_variation_field_mapper;
    $this->renderer = $renderer;
    $this->commerceBundleVariationService = $bundle_variation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('commerce_product_bundles.bundle_variation_mapper'),
      $container->get('renderer'),
      $container->get('commerce_product_bundles.bundle_variation_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'view_mode' => 'default',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $view_modes = $this->entityDisplayRepository->getViewModes('commerce_bundle_variation');
    $view_mode_labels = array_map(function ($view_mode) {
      return $view_mode['label'];
    }, $view_modes);

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle variations value display mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => ['default' => $this->t('Default')] + $view_mode_labels,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Bundle variations value display mode: @mode', [
      '@mode' => $this->getSetting('view_mode'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $build = [
      '#theme' => 'item_list',
      '#title' => $items->getEntity()->label(),
      '#items' => [],
    ];

    foreach ($items as $key => $item) {
      foreach ($this->bundleVariationFieldMapper->prepareBundleVariations($item->entity) as $ref_product_id => $bundle_variation) {
        $savings = $this->commerceBundleVariationService->calculateSavings([], $item->entity);
        $title = $item->entity->label();

        // Show savings only if it is higher that 0.
        if($savings->isPositive() && !$savings->isZero()) {
          $options = [
            'currency_display' => 'symbol',
            'minimum_fraction_digits' => 0,
          ];

          $calculated_savings_result = new PriceCalculatorResult($savings, $savings);
          $cal_savings_price = $calculated_savings_result->getCalculatedPrice();
          $cal_savings_original_number = $cal_savings_price->getNumber();
          $calc_savings_base_number = $calculated_savings_result->getBasePrice()->getNumber();
          $calc_savings_currency_code = $cal_savings_price->getCurrencyCode();

          $savings_price = [
            '#theme' => 'commerce_savings_price_calculated',
            '#result' => $calculated_savings_result,
            '#calculated_price' => \Drupal::service('commerce_price.currency_formatter')->format($cal_savings_original_number, $calc_savings_currency_code, $options),
            '#base_price' => \Drupal::service('commerce_price.currency_formatter')->format($calc_savings_base_number, $calc_savings_currency_code, $options),
            '#adjustments' => $calculated_savings_result->getAdjustments(),
            '#cache' => [
              'tags' => $bundle_variation->getCacheTags(),
              'contexts' => Cache::mergeContexts($bundle_variation->getCacheContexts(), [
                'languages:' . LanguageInterface::TYPE_INTERFACE,
                'country',
              ]),
            ],
          ];

          // Render label.
          $savings_markup = [
            '#theme' => 'commerce_bundle_savings_label',
            '#savings' => $savings_price,
            '#label' => $title
          ];

          // Render label.
          $title = \Drupal::service('renderer')->render($savings_markup);
        }

        $build_ref = [
          '#theme' => 'item_list',
          '#title' => render( $title),
          '#items' => [$item->entity->id() => $this->processRefValues($bundle_variation)],
        ];
        $build['#items'][$item->entity->id()] = $build_ref;
      }
    }

    $elements[] = $build;

    return $elements;
  }

  /**
   * Process referenced values.
   *
   * @param $bundle_variation
   *
   * @return array
   */
  public static function processRefValues($bundle_variation) {
    $build = [
      '#theme' => 'item_list',
      '#items' => [],
    ];
    foreach ($bundle_variation->getRefVariations() as $key => $variation) {
      $build['#items'][$variation->id()] = $variation->label();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_product_bundle' && $field_name == 'bundle_variations';
  }

}
