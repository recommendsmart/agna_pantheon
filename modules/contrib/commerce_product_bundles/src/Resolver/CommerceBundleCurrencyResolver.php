<?php

namespace Drupal\commerce_product_bundles\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_exchanger\ExchangerCalculatorInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\commerce_currency_resolver\CurrentCurrencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class CommerceBundleCurrencyResolver
 *
 * @package Drupal\commerce_product_bundles\Resolver
 */
class CommerceBundleCurrencyResolver implements PriceResolverInterface {

  /**
   * The current currency.
   *
   * @var \Drupal\commerce_currency_resolver\CurrentCurrencyInterface
   */
  protected $currentCurrency;

  /**
   * Exchanger Calculator.
   *
   * @var \Drupal\commerce_exchanger\ExchangerCalculatorInterface
   */
  protected $priceExchanger;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new CommerceCurrencyResolver object.
   *
   * @param \Drupal\commerce_currency_resolver\CurrentCurrencyInterface $current_currency
   *   The currency manager.
   * @param \Drupal\commerce_exchanger\ExchangerCalculatorInterface $price_exchanger
   *   Price exchanger calculator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(CurrentCurrencyInterface $current_currency, ExchangerCalculatorInterface $price_exchanger, ConfigFactoryInterface $config_factory) {
    $this->currentCurrency = $current_currency;
    $this->priceExchanger = $price_exchanger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {

    // Default price.
    $price = NULL;

    // Get field from context.
    $field_name = $context->getData('field_name', 'price');

    // @see \Drupal\commerce_price\Resolver\DefaultPriceResolver
    if ($field_name === 'price') {
      $price = $entity->get('price')->get(0)->toPrices();
    }

    // Loading orders trough drush, or any cli task
    // will resolve price by current conditions in which cli is
    // (country, language, current store) - this will result in
    // currency exception. We need to return existing price.
    if (PHP_SAPI === 'cli') {
      return $price['USD'];
    }

    // Get current resolved currency.
    $resolved_currency = $this->currentCurrency->getCurrency();

    // Get resolved currency Price.
    // Defaults to USD.
    return isset($price[$resolved_currency]) ? $price[$resolved_currency] : $price['USD'];
  }

}
