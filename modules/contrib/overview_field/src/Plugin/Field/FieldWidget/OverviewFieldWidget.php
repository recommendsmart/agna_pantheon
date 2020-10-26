<?php

namespace Drupal\overview_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'overview_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "overview_field_widget",
 *   label = @Translation("Overview field widget"),
 *   field_types = {
 *     "overview_field"
 *   }
 * )
 */
class OverviewFieldWidget extends WidgetBase {
  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $class = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $class->moduleHandler = $container->get('module_handler');
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $options = [];

    $this->moduleHandler->alter('overview_field_options', $options);

    $element['value'] = $element + [
      '#type' => 'select',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#required' => FALSE,
      '#options' => $options,
      '#empty_option' => $this->t('No overview'),
      '#empty_value' => '',
    ];

    return $element;
  }

}
