<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_currency_resolver\CurrentCurrencyInterface;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'bundle_price_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bundle_price_field_formatter",
 *   module = "commerce_product_bundles",
 *   label = @Translation("Commerce product bundle price formatter"),
 *   field_types = {
 *     "commerce_currencies_price"
 *   }
 * )
 */
class BundleVariationPriceDefaultFormatter extends PriceDefaultFormatter {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * @var \Drupal\commerce_currency_resolver\CurrentCurrency
   */
  protected $currentCurrency;

  /**
   * BundleVariationPriceDefaultFormatter constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   * @param \Drupal\commerce_currency_resolver\CurrentCurrencyInterface $current_currency
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode,
                              array $third_party_settings, CurrencyFormatterInterface $currency_formatter, CurrentCurrencyInterface $current_currency) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $currency_formatter);

    $this->currentCurrency = $current_currency;
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
      $container->get('commerce_price.currency_formatter'),
      $container->get('commerce_currency_resolver.current_currency')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $options = $this->getFormattingOptions();
    $elements = [];
    foreach ($items as $delta => $item) {
      $prices = $item->prices;
      $resolved_currency = $this->currentCurrency->getCurrency();
      $price = isset($prices[$resolved_currency]) ? $prices[$resolved_currency] : $prices['USD'];
      $elements[$delta] = [
        '#markup' => $this->currencyFormatter->format($price['number'], $price['currency_code'], $options),
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ],
        ],
      ];
    }

    return $elements;
  }

}
