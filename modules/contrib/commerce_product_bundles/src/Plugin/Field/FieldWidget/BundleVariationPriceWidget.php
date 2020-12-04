<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldWidget;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce\Context;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\commerce_order\PriceCalculatorInterface;
use Drupal\commerce_order\PriceCalculatorResult;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_product_bundles\Entity\ProductBundleVariation;
use Drupal\commerce_product_bundles\Service\ProductBundleVariationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'bundle_variation_price' widget.
 *
 * This is not widget but 'Formatter' to display bundle price in bundle variation form.
 *
 * @FieldWidget(
 *   id = "bundle_variation_price",
 *   label = @Translation("Bundle variation price"),
 *   field_types = {
 *     "commerce_price",
 *   }
 * )
 */
class BundleVariationPriceWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The price calculator.
   *
   * @var \Drupal\commerce_order\PriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * The commerce product bundle service.
   *
   * @var \Drupal\commerce_product_bundles\service\ProductBundleVariationServiceInterface
   */
  protected $commerceProductBundleService;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Request stack service.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * BundleVariationPriceWidget constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\commerce_order\PriceCalculatorInterface $price_calculator
   * @param \Drupal\commerce_product_bundles\Service\ProductBundleVariationServiceInterface $commerce_product_bundles_service
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings,
                              CurrencyFormatterInterface $currency_formatter, AdjustmentTypeManager $adjustment_type_manager,
                              CurrentStoreInterface $current_store, AccountInterface $current_user, PriceCalculatorInterface $price_calculator,
                              ProductBundleVariationServiceInterface $commerce_product_bundles_service, ModuleHandlerInterface $module_handler,
                              EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->currencyFormatter = $currency_formatter;
    $this->adjustmentTypeManager = $adjustment_type_manager;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->priceCalculator = $price_calculator;
    $this->commerceProductBundleService = $commerce_product_bundles_service;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
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
      $configuration['third_party_settings'],
      $container->get('commerce_price.currency_formatter'),
      $container->get('plugin.manager.commerce_adjustment_type'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user'),
      $container->get('commerce_order.price_calculator'),
      $container->get('commerce_product_bundles.bundle_variation_service'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $items[$delta]->getEntity();

    // Get storage.
    $storage = $form_state->getStorage();

    if (!$items[$delta]->isEmpty()) {

      if ($order_item->getFields()['purchased_entity'] instanceof EntityReferenceFieldItemList) {
        $variation_id = $storage['selected_variation'] ?? '';
        // Get all data.
        $bundle_variation_id = !empty($variation_id) ? $variation_id : $order_item->getFields()['purchased_entity']->getString();

        $bundle_variation = ProductBundleVariation::load($bundle_variation_id);
        $context = new Context($this->currentUser, $this->currentStore->getStore(), NULL, []);

        /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
        $purchasable_entity = $bundle_variation;
        // Get calculated price > include promotions.
        $result = $this->priceCalculator->calculate($purchasable_entity, 1, $context, ['promotion' => 'promotion']);
        $calculated_price = $result->getCalculatedPrice();
        $base_price = $result->getBasePrice();
        $number = $calculated_price->getNumber();
        $base_number = $result->getBasePrice()->getNumber();
        $currency_code = $calculated_price->getCurrencyCode();
        $options = $this->getFormattingOptions();

        $markup = [
          '#theme' => 'commerce_bundle_bundle_price_calculated',
          '#result' => $result,
          '#calculated_price' => $this->currencyFormatter->format($number, $currency_code, $options),
          '#base_price' => $this->currencyFormatter->format($base_number, $currency_code, $options),
          '#adjustments' => $result->getAdjustments(),
          '#cache' => [
            'tags' => $purchasable_entity->getCacheTags(),
            'contexts' => Cache::mergeContexts($purchasable_entity->getCacheContexts(), [
              'languages:' . LanguageInterface::TYPE_INTERFACE,
              'country',
            ]),
          ],
        ];

        // Set bundle price tpl..
        $element = [
          '#theme' => 'commerce_bundle_price_calculated',
          '#bundle_price' => $markup,
          '#original_price' => NULL,
          '#savings_price' => NULL
        ];

        // Get original price.
        $purchased_entity = [];
        $selected_vars = $form_state->getValue('purchased_entity');
        if(empty($selected_vars)) {
          $user_input = $form_state->getUserInput();
          $selected_vars = isset($user_input['purchased_entity']) ? $user_input['purchased_entity'] : NULL;
        }
        if(isset($selected_vars[0]['bundle_variations_ref_options']) && !empty($selected_vars[0]['bundle_variations_ref_options'])){
          $purchased_entity[0]['bundle_variations_ref_options'] = $selected_vars[0]['bundle_variations_ref_options'] ?? '';
        }
        if(isset($selected_vars[0]['quantity']) && !empty($selected_vars[0]['quantity'])){
          $purchased_entity[0]['quantity'] = $selected_vars[0]['quantity'] ?? '';
        }

        // Show original price only if we do not have discount on bundle.
        if(!$base_price->greaterThan($calculated_price)){
          $calculated_original_value = $this->commerceProductBundleService->calculateOriginalPrice($purchased_entity, $bundle_variation);

          // Show original price only if it is greater than bundle price.
          if($calculated_original_value->greaterThan($calculated_price)){
            $calculated_price_result = new PriceCalculatorResult($calculated_original_value, $calculated_original_value);

            $cal_original_price = $calculated_price_result->getCalculatedPrice();
            $cal_original_number = $cal_original_price->getNumber();
            $calc_base_number = $calculated_price_result->getBasePrice()->getNumber();
            $calc_currency_code = $cal_original_price->getCurrencyCode();

            $original_price_markup = [
              '#theme' => 'commerce_original_price_calculated',
              '#result' => $calculated_price_result,
              '#calculated_price' => $this->currencyFormatter->format($cal_original_number, $calc_currency_code, $options),
              '#base_price' => $this->currencyFormatter->format($calc_base_number, $calc_currency_code, $options),
              '#adjustments' => $calculated_price_result->getAdjustments(),
              '#cache' => [
                'tags' => $purchasable_entity->getCacheTags(),
                'contexts' => Cache::mergeContexts($purchasable_entity->getCacheContexts(), [
                  'languages:' . LanguageInterface::TYPE_INTERFACE,
                  'country',
                ]),
              ],
            ];

            $element['#original_price'] = $original_price_markup;
          }
        }

        // Calculate savings price.
        $savings = $this->commerceProductBundleService->calculateSavings($selected_vars, $bundle_variation);
        // Show savings only if it is higher that 0.
        if($savings->isPositive() && !$savings->isZero()) {
          $calculated_savings_result = new PriceCalculatorResult($savings, $savings);

          $cal_savings_price = $calculated_savings_result->getCalculatedPrice();
          $cal_savings_original_number = $cal_savings_price->getNumber();
          $calc_savings_base_number = $calculated_savings_result->getBasePrice()->getNumber();
          $calc_savings_currency_code = $cal_savings_price->getCurrencyCode();

          $savings_price_markup = [
            '#theme' => 'commerce_savings_price_calculated',
            '#result' => $calculated_savings_result,
            '#calculated_price' => $this->currencyFormatter->format($cal_savings_original_number, $calc_savings_currency_code, $options),
            '#base_price' => $this->currencyFormatter->format($calc_savings_base_number, $calc_savings_currency_code, $options),
            '#adjustments' => $calculated_savings_result->getAdjustments(),
            '#cache' => [
              'tags' => $purchasable_entity->getCacheTags(),
              'contexts' => Cache::mergeContexts($purchasable_entity->getCacheContexts(), [
                'languages:' . LanguageInterface::TYPE_INTERFACE,
                'country',
              ]),
            ],
          ];

          $element['#savings_price'] = $savings_price_markup;
        }
      }
    }

    return $element;
  }

  /**
   * Gets the formatting options for the currency formatter.
   *
   * @return array
   *   The formatting options.
   */
  protected function getFormattingOptions() {
    return [
      'currency_display' => 'symbol',
      'minimum_fraction_digits' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type === 'commerce_order_item' && $field_name === 'unit_price';
  }

}
