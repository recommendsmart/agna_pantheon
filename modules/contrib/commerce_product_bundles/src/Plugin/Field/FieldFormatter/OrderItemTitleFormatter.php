<?php

namespace Drupal\commerce_product_bundles\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'order_item_title_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "order_item_title_formatter",
 *   module = "commerce_product_bundles",
 *   label = @Translation("Order item title formatter"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class OrderItemTitleFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * OrderItemTitleFormatter constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings,
                              LanguageManagerInterface $language_manager, Connection $database) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->languageManager = $language_manager;
    $this->database = $database;
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
      $container->get('language_manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'include_ref' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['include_ref'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include bundle contains referenced variations.'),
      '#description' => $this->t('Include bundle contains referenced variations in title.'),
      '#default_value' => $this->getSetting('include_ref'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('include_ref')) {
      $summary[] = $this->t('Include bundle contains referenced variations.');
    }
    else {
      $summary[] = $this->t('Do not include bundle contains referenced variations.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if (empty($items)) {
      return $elements;
    }

    foreach ($items as $delta => $item) {
      $order_item = $item->getEntity();
      $current_lng = $this->languageManager->getCurrentLanguage()->getId();

      switch ($order_item->bundle()){
        case 'bundle':
          // Construct purchased eck title.
          $bundle_variation = $order_item->getPurchasedEntity();
          $bundle_product = $bundle_variation->getBundleProduct();
          if($bundle_product->hasTranslation($current_lng)){
            $bundle_product->getTranslation($current_lng);
          }

          // Construct title.
          $order_item_title = $bundle_product->getTitle() . ' - ' . $order_item->getTitle();

          // Include bundle contains.
          if($this->getSetting('include_ref')) {
            // Get list of all referenced variations.
            $ref_variations_data = [];
            $ref_variations = $order_item->get('field_product_variation_ref')->getValue();
            foreach ($ref_variations as $key => $ref_variation) {
              if(isset($ref_variation['product_var_id']) && isset($ref_variation['quantity'])) {
                $query = $this->database->select('commerce_product_variation_field_data', 'pv')
                  ->fields('pv', ['title', 'sku'])
                  ->condition('pv.variation_id', $ref_variation['product_var_id'])
                  ->condition('pv.langcode', $current_lng)
                  ->execute();
                $variation_data = $query->fetchAll();
                foreach ($variation_data as $data) {
                  $ref_variations_data[] = $ref_variation['quantity'] . 'X ' . $data->title . ' (SKU: ' . $data->sku . ')';
                }
              }
            }
            if(!empty($ref_variations_data)) {
              $bundles_markup = implode('<br>', $ref_variations_data);
              $markup = $order_item_title . ':<br> ' . $bundles_markup;
              $order_item_title = new FormattableMarkup($markup,[]);
            }
          }

          $elements[$delta] = [
            '#markup' =>  '<div>' . $order_item_title . '</div>'
          ];

          break;
        default:
          $elements[$delta] = [
            '#markup' =>  $order_item->getTitle()
          ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order_item' && $field_name == 'title';
  }

}
