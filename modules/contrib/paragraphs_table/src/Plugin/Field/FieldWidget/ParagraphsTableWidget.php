<?php

namespace Drupal\paragraphs_table\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Plugin implementation of the 'paragraphs_table_widget' widget.
 *
 * @FieldWidget(
 *   id = "paragraphs_table_widget",
 *   label = @Translation("Paragraphs table"),
 *   description = @Translation("Paragraphs table form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsTableWidget extends ParagraphsWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'vertical' => FALSE,
        'paste_clipboard' => FALSE,
        'show_all' => FALSE,
        'duplicate' => FALSE,
        'features' => ['duplicate' => 'duplicate'],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['vertical'] = [
      '#type' => 'checkbox',
      '#title' => t('Table vertical'),
      '#description' => t('If checked, table data will show in vertical mode.'),
      '#default_value' => !empty($this->getSetting('vertical')) ? $this->getSetting('vertical') : FALSE,
    ];
    $elements['paste_clipboard'] = [
      '#type' => 'checkbox',
      '#title' => t('Paste from clipboard'),
      '#description' => t('Add multiple rows, you can paste data from Excel'),
      '#default_value' => !empty($this->getSetting('paste_clipboard')) ? $this->getSetting('paste_clipboard') : FALSE,
    ];
    $cardinality = $this->fieldDefinition->get('fieldStorage')
      ->get('cardinality');
    if ($cardinality > 1) {
      $elements['show_all'] = [
        '#type' => 'checkbox',
        '#title' => t('Show all %cardinality items in form', ['%cardinality' => $cardinality]),
        '#description' => t('If checked, remove button add more.'),
        '#default_value' => !empty($this->getSetting('show_all')) ? $this->getSetting('show_all') : FALSE,
      ];
    }
    if (!in_array($cardinality, range(0, 3))) {
      $elements['features'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Enable widget features'),
        '#options' => [
          'duplicate' => $this->t('Duplicate'),
          //reserve for future features
        ],
        '#default_value' => $this->getSetting('features'),
        '#multiple' => TRUE,
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if (!empty($this->getSetting('vertical'))) {
      $summary[] = t('Table mode vertical');
    }
    if (!empty($this->getSetting('paste_clipboard'))) {
      $summary[] = t('Paste from Excel');
    }
    if (!empty($this->getSetting('show_all'))) {
      $cardinality = $this->fieldDefinition->get('fieldStorage')
        ->get('cardinality');
      $summary[] = t('Show all %cardinality elements in form', ['%cardinality' => $cardinality]);
    }
    $features = array_filter($this->getSetting('features'));
    if (!empty($features)) {
      $summary[] = $this->t('Features: @features', ['@features' => implode(', ', $features)]);
    }

    return $summary;
  }

  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    $target_type = $this->getFieldSetting('target_type');
    $default_type = $this->getDefaultParagraphTypeMachineName();

    $formDisplay = \Drupal::service('entity_display.repository')
      ->getFormDisplay($target_type, $default_type);
    $components = $formDisplay->getComponents();
    uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
    $paragraphs_entity = \Drupal::entityTypeManager()->getStorage($target_type)
      ->create(['type' => $default_type]);
    $field_definitions = $paragraphs_entity->getFieldDefinitions();

    foreach ($components as $name => $setting) {
      if ($field_definitions[$name] instanceof FieldConfigInterface) {
        $elements["#paragraphsTable"]['#fields'][$name] = $field_definitions[$name];
      }
    }
    $field_name = $this->fieldDefinition->getName();
    $elements["#paragraphsTable"]["#widget_state"] = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $elements["#paragraphsTable"]["#table_vertical"] = $this->getSetting('vertical');
    $elements["#paragraphsTable"]["#paste_clipboard"] = $this->getSetting('paste_clipboard');
    $elements["#paragraphsTable"]["#show_all"] = $this->getSetting('show_all');
    $elements["#paragraphsTable"]["#feature"] = $this->getSetting('features');
    return $elements;
  }
}
