<?php

namespace Drupal\paragraphs_table\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'paragraphs_table_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraphs_table_formatter",
 *   module = "paragraphs_table",
 *   label = @Translation("Paragraphs table"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsTableFormatter extends EntityReferenceFormatterBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityDisplayRepository = $entity_display_repository;
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
      $container->get('entity_display.repository')
    );
  }
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
        'view_mode' => 'default',
        'vertical' => FALSE,
        'caption' => '',
        'mode' => '',
        'chart_type' => '',
        'chart_width' => 900,
        'chart_height' => 300,
        'import' => '',
        'empty_cell_value' => FALSE,
        'empty' => FALSE,
        'ajax' => FALSE,
        'custom_class' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settingForm = [
      'view_mode' => [
        '#type' => 'select',
        '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
        '#title' => $this->t('View mode'),
        '#default_value' => $this->getSetting('view_mode'),
        '#required' => TRUE,
      ],
      'caption' => [
        '#title' => $this->t('Caption'),
        '#description' => $this->t('Caption of table'),
        '#type' => 'textfield',
        '#default_value' => $this->getSettings()['caption'],
      ],
      'vertical' => [
        '#title' => $this->t('Table vertical'),
        '#description' => $this->t('If checked, table data will show in vertical mode'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSettings()['vertical'],
      ],
      'mode' => [
        '#title' => $this->t('Table Mode'),
        '#description' => $this->t('Select the table extension.'),
        '#type' => 'select',
        '#default_value' => $this->getSettings()['mode'],
        '#options' => $this->getConfigurableViewModes(),
        '#empty_option' => $this->t('Default'),
        '#states' => [
          'visible' => [
            'input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][vertical]"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'chart_type' => [
        '#title' => $this->t('Chart type'),
        '#description' => '<a href="https://developers-dot-devsite-v2-prod.appspot.com/chart/interactive/docs/gallery" target="_blank">Google charts</a>',
        '#type' => 'select',
        '#default_value' => $this->getSettings()['chart_type'],
        '#options' => $this->googleChartsOption(),
        '#empty_option' => $this->t('Default'),
        '#states' => [
          'visible' => [
            'select[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][mode]"]' => ['value' => 'googleCharts'],
          ],
        ],
      ],
      'chart_width' => [
        '#title' => $this->t('Chart width'),
        '#type' => 'number',
        '#default_value' => $this->getSettings()['chart_width'],
        '#states' => [
          'visible' => [
            'select[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][mode]"]' => ['value' => 'googleCharts'],
          ],
        ],
      ],
      'chart_height' => [
        '#title' => $this->t('Chart height'),
        '#type' => 'number',
        '#default_value' => $this->getSettings()['chart_height'],
        '#states' => [
          'visible' => [
            'select[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][mode]"]' => ['value' => 'googleCharts'],
          ],
        ],
      ],
      'empty_cell_value' => [
        '#title' => $this->t('Fill Blank Cells in table'),
        '#description' => $this->t('The string which should be displayed in empty cells. Defaults to an empty string.'),
        '#type' => 'textfield',
        '#default_value' => $this->getSettings()['empty_cell_value'],
      ],
      'empty' => [
        '#title' => $this->t('Hide empty columns'),
        '#description' => $this->t('If enabled, hide empty paragraphs table columns'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSettings()['empty'],
        '#states' => [
          'visible' => [
            'input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][vertical]"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'ajax' => [
        '#title' => $this->t('Load table with ajax'),
        '#description' => $this->t('User for big data, ajax will load table data'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSettings()['ajax'],
        '#states' => [
          'visible' => [
            'input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][vertical]"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'custom_class' => [
        '#title' => $this->t('Set table class'),
        '#type' => 'textfield',
        '#default_value' => $this->getSettings()['custom_class'],
      ],
    ];
    if (\Drupal::service('module_handler')->moduleExists('quick_data')) {
      $settingForm['import'] = [
        '#title' => $this->t('Import link title'),
        '#description' => $this->t("Leave it blank if you don't want to import csv data"),
        '#type' => 'textfield',
        '#default_value' => $this->getSettings()['import'],
      ];
    }
    return $settingForm + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $summary[] = $this->t('Rendered as @view_mode', ['@view_mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode]);

    if (!empty($this->getSetting('caption'))) {
      $summary[] = $this->t('Caption: @caption', ['@caption' => $this->getSetting('caption')]);
    }
    if (!empty($this->getSetting('vertical'))) {
      $summary[] = $this->t('Table mode vertical');
    }
    if (!empty($this->getSetting('mode'))) {
      $summary[] = $this->t('Mode: @mode', ['@mode' => $this->getSetting('mode')]);
    }
    if (!empty($this->getSetting('chart_type'))) {
      $summary[] = $this->t('Chart type: @type', ['@type' => $this->getSetting('chart_type')]);
    }
    if (!empty($this->getSetting('import'))) {
      $summary[] = $this->t('Label import csv: @import', ['@import' => $this->getSetting('import')]);
    }
    if (!empty($this->getSetting('empty_cell_value'))) {
      $summary[] = $this->t('Replace empty cells with: @replace', ['@replace' => $this->getSetting('empty_cell_value')]);
    }
    if (!empty($this->getSetting('empty'))) {
      $summary[] = $this->t('Hide empty columns');
    }
    if (!empty($this->getSetting('ajax'))) {
      $summary[] = $this->t('Load ajax');
    }
    if (!empty($this->getSetting('custom_class'))) {
      $summary[] = $this->t('Custom class: @class', ['@class' => $this->getSetting('custom_class')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $setting = $this->getSettings();
    $targetType = $this->getFieldSetting('target_type');
    $field_definition = $items->getFieldDefinition();
    $entity = $items->getEntity();
    $entityId = $entity->id();
    $targetBundle = array_key_first($field_definition->getSetting("handler_settings")["target_bundles"]);
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
    $paragraphs_entity = \Drupal::entityTypeManager()->getStorage($targetType)
      ->create(['type' => $targetBundle]);
    $field_definitions = $paragraphs_entity->getFieldDefinitions();
    $view_mode = $setting['view_mode'];
    $viewDisplay = \Drupal::service('entity_display.repository')
      ->getViewDisplay($targetType, $targetBundle, $view_mode);
    $components = $viewDisplay->getComponents();
    uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    $table = $table_header = $fields = [];
    foreach ($components as $field_name => $component) {
      if ($field_definitions[$field_name] instanceof FieldConfigInterface) {
        $fields[$field_name] = $field_definitions[$field_name];
        $table_header[$field_name] = (string) $this->t($field_definitions[$field_name]->getLabel());
      }
    }
    $entities = $this->getEntitiesToView($items, $langcode);
    $table_rows = $notEmptyColumn = [];
    if (in_array($setting['mode'], ['datatables', 'bootstrapTable']) || empty($setting['mode'])) {
      if (empty($setting["ajax"]) && !empty($entities)) {
        $table_rows = $this->getPreparedRenderedEntities($targetType, $targetBundle, $entities, $fields, $notEmptyColumn, $view_mode);
        //remove empty columns
        if (!empty($setting["empty"]) && !empty($notEmptyColumn)) {
          foreach ($table_header as $field_name => $label_column) {
            if (empty($notEmptyColumn[$field_name])) {
              unset($table_header[$field_name]);
              foreach ($table_rows as $delta => &$row) {
                unset($row[$field_name]);
              }
            }
          }
        }
      }

      if (!empty($setting["vertical"])) {
        $table = $this->getTableVertical($table_header, $table_rows, $entities);
      } else {
        $table = $this->getTable($table_header, $table_rows, $entities);
        if(empty($table_rows) && $setting['empty']){
          unset($table["#header"]);
        }
      }
    }
    $table_id = implode('-', [$targetType, $targetBundle]);
    $table['#attributes']['id'] = $table_id;
    $table['#attributes']['class'][] = 'paragraphs-table';
    if (!empty($setting['caption'])) {
      $table['#caption'] = $this->t($setting['caption']);
    }
    $custom_class = '';
    if (!empty($setting['custom_class'])) {
      $table['#attributes']['class'][] = $setting['custom_class'];
      $custom_class = ' class="' . $setting['custom_class'] . '"';
    }
    $fieldName = $field_definition->getName();
    switch ($setting['mode']) {
      case 'datatables':
        $datatable_options = $this->datatablesOption($table_header, $components, $langcode);

        if (!empty($setting["ajax"]) && $entityId) {
          $url = Url::fromRoute('paragraphs_item.json', [
            'field_name' => $fieldName,
            'host_type' => $field_definition->getTargetEntityTypeId(),
            'host_id' => $entityId,
          ]);
          $datatable_options["ajax"] = $url->toString();
        }

        $table['#attributes']['width'] = '100%';
        $table['#attached']['library'][] = 'paragraphs_table/datatables_core';
        $table['#attached']['drupalSettings']['datatables']['#' . $table_id] = $datatable_options;
        $table['#attached']['library'][] = 'paragraphs_table/datatables';
        break;
      case 'bootstrapTable':
        $bootstrapTable_options = $this->bootstrapTableOption($table_header, $components, $langcode);
        foreach ($bootstrapTable_options as $dataTable => $valueData) {
          $table['#attributes']["data-$dataTable"] = $valueData;
        }
        if (!empty($setting["ajax"]) && $entityId) {
          $url = Url::fromRoute('paragraphs_item.json', [
            'field_name' => $fieldName,
            'host_type' => $field_definition->getTargetEntityTypeId(),
            'host_id' => $entityId,
          ], ['query' => ['type' => 'rows']]);
          $table['#attributes']["data-url"] = $url->toString();
        }
        foreach ($table_header as $field_name => $field_label) {
          $table_header[$field_name] = [
            'data' => $field_label,
            'data-field' => $field_name,
            'data-sortable' => "true",
          ];
        }
        $table['#header'] = $table_header;
        $table['#attached']['library'][] = 'paragraphs_table/bootstrapTable';
        if (!\Drupal::moduleHandler()->moduleExists('fontawesome')) {
          $table['#attached']['library'][] = 'paragraphs_table/bootstrapTable_fontawesome';
        }
        break;
      case 'googleCharts':
        $options = $this->googleChartsOption($setting['chart_type']);
        $options['url'] = FALSE;
        if (!empty($setting['caption'])) {
          $options['title'] = $setting['caption'];
        }

        $table = [
          '#type' => 'markup',
          '#markup' => '<div' . $custom_class . ' id="' . $table_id . '" style="width: ' . $setting['chart_width'] . 'px; height: ' . $setting['chart_height'] . 'px;"></div>',
        ];
        $table['#attached']['drupalSettings']['googleCharts']['#' . $table_id] = [
          'id' => $table_id,
          'type' => !empty($setting['chart_type']) ? $setting['chart_type'] : 'BarChart',
          'options' => $options,
        ];
        if (empty($setting["ajax"])) {
          $data = $this->getData($targetType, $targetBundle, $entities, $fields, $notEmptyColumn, $view_mode);
          if (empty($options['notHeader'])) {
            $data = array_merge([array_values($table_header)], $data);
          }
          $table['#attached']['drupalSettings']['googleCharts']['#' . $table_id]['data'] = $data;
        }
        else {
          $url = Url::fromRoute('paragraphs_item.jsonData', [
            'field_name' => $fieldName,
            'host_type' => $field_definition->getTargetEntityTypeId(),
            'host_id' => $entityId,
          ]);
          $table['#attached']['drupalSettings']['googleCharts']['#' . $table_id]['options']['url'] = $url->toString();
        }

        $table['#attached']['library'][] = 'paragraphs_table/googleCharts';
        break;
      default:
        if (!empty($setting["ajax"])) {
          $url = Url::fromRoute('paragraphs_item.ajax', [
            'field_name' => $fieldName,
            'host_type' => $field_definition->getTargetEntityTypeId(),
            'host_id' => $entityId,
          ]);
          $table['#attached']['drupalSettings']['paragraphsTable']['#' . $table_id] = [
            'id' => $table_id,
            'view_mode' => $view_mode,
            'url' => $url->toString(),
          ];
          $table['#attached']['library'][] = 'paragraphs_table/paragraphsTable';
        }
        break;

    }
    //alter table results
    \Drupal::moduleHandler()
      ->alter('paragraphs_table_formatter', $table, $table_id);

    $form = NULL;
    $hasPermission = true;
    $user = \Drupal::currentUser();
    $permissions = [
      'bypass node access',
      'administer nodes',
      'administer paragraphs_item fields',
      'create ' . $fieldName,
      'edit ' . $fieldName,
      'edit own ' . $fieldName,
    ];
    foreach ($permissions as $permission){
      if ($user->hasPermission($permission)) {
        $hasPermission = TRUE;
        break;
      }
    }

    if ($hasPermission) {
      $form_state = (new FormState())
        ->set('langcode', $langcode)
        ->disableRedirect()
        ->addBuildInfo('args', [$entity, $field_definition->getName(), $targetBundle]);
      $form = \Drupal::formBuilder()
        ->buildForm('\Drupal\paragraphs_table\Form\ActionButtonForm', $form_state);
      $triggering_input = $form_state->getUserInput();
      if(!empty($triggering_input)){
        $edit_btn = 'edit_'.$fieldName;
        $triggering_element = $form_state->getTriggeringElement();
        $temp = explode("_",$triggering_element["#name"]);
        $trigger_mode = end($temp);
        if(!empty($triggering_input[$edit_btn]) || in_array($trigger_mode, ['remove','duplicate'])){
          $table['#attributes']['class'][] = 'hidden';
        }
      }
    }
    return [$table, $form];
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

  /**
   * Get the entities which will make up the table.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   *
   * @return array
   *   An array of loaded entities.
   */
  protected function getEntities(FieldItemListInterface $items) {
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()
      ->getSetting('target_type');
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities = [];
    foreach ($items as $item) {
      $entity_id = $item->getValue()['target_id'];
      if ($entity_id) {
        $entity = $entity_storage->load($entity_id);
        if ($entity && $entity->access('view')) {
          $entities[] = $entity;
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getTable($table_columns, $table_rows, $entities) {
    $table = [
      '#theme' => 'table',
      '#rows' => $table_rows,
      '#header' => $table_columns,
    ];
    $this->cache_metadata($entities, $table);
    return $table;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($type, $bundle, $entities, $fields, &$notEmptyColumn, $view_mode = 'default') {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    // When a display renderer doesn't exist, fall back to the default.
    $renderer = $storage->load(implode('.', [$type, $bundle, $view_mode]));
    $setting = $this->getSettings();
    $data = [];
    foreach ($entities as $delta => $entitie) {
      $table_entity = $renderer->build($entitie);
      foreach ($fields as $field_name => $field) {
        $table_entity[$field_name]['#label_display'] = 'hidden';
        $value = trim(strip_tags(render($table_entity[$field_name])));
        if (in_array($field->getType(), [
          'integer',
          'list_integer',
          'number_integer',
        ])) {
          $value = (int) $value;
        }
        if (in_array($field->getType(), ['boolean'])) {
          $list_value = $table_entity[$field_name]["#items"]->getValue();
          $value = (int) $list_value[0]['value'];
        }
        if (in_array($field->getType(), [
          'decimal',
          'list_decimal',
          'number_decimal',
          'float',
          'list_float',
          'number_float',
        ])) {
          $value = (float) $value;
        }
        if (!empty($value)) {
          $notEmptyColumn[$field_name] = TRUE;
        }
        elseif (!empty($setting["empty_cell_value"])) {
          $value = $setting["empty_cell_value"];
        }

        $data[$delta][] = $value;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableVertical($table_columns, $table_rows, $entities) {
    $rows = [];
    foreach ($table_rows as $delta => $row) {
      if (count($table_rows) > 1) {
        $operation = !empty($row['data']['operation']) ? $row['data']['operation'] : '';
        $rows[] = [
          'data' => [['data' => $operation, 'colspan' => 2]],
          'class' => ['paragraphs-item', 'action'],
          'data-quickedit-entity-id' => $row['data-quickedit-entity-id'],
          'data-id' => $row['data-id'],
          'data-type' => $row['data-type'],
        ];
      }
      foreach ($row['data'] as $field_name => $value) {
        if (!empty($table_columns[$field_name])) {
          $rows[] = [
            'data' => [
              'label' => [
                'data' => $this->t($table_columns[$field_name]),
                'class' => ['field__label', $field_name],
              ],
              'item' => [
                'data' => $value,
                'class' => ['field__item', $field_name],
              ],
            ],
            'class' => ['field-paragraphs-item'],
            'id' => 'item-' . $delta,
          ];
        }
      }
    }
    $table = [
      '#theme' => 'table',
      '#rows' => $rows,
    ];
    $this->cache_metadata($entities, $table);

    return $table;
  }

  protected function cache_metadata($entities, &$table) {
    $cache_metadata = new CacheableMetadata();
    foreach ($entities as $entity) {
      $cache_metadata->addCacheableDependency($entity);
      $cache_metadata->addCacheableDependency($entity->access('view', NULL, TRUE));
    }
    $cache_metadata->applyTo($table);
  }

  /**
   * Prepare all of the given entities for rendering with applicable fields.
   *
   * @param string $type
   *   The entity type of the given entities.
   * @param string $bundle
   *   The bundle that the entities are composed of.
   * @param array $entities
   *   An array of entities to put into the table.
   * @param array $fields
   *   An array of fields avaible for render.
   * @param array $notEmptyColumn
   *   An array of detect empty colum.
   * @param array $settings
   *   The settings array from the field formatter base containing keys:
   *     - view_mode: The target view mode to render the field settings from.
   *     - empty_cell_value: The value to show in empty cells.
   *
   * @return array
   *   An array of entities with applicable fields prepared for rendering.
   */
  protected function getPreparedRenderedEntities($type, $bundle, $entities, $fields, &$notEmptyColumn, $view_mode = 'default') {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    // When a display renderer doesn't exist, fall back to the default.
    $renderer = $storage->load(implode('.', [$type, $bundle, $view_mode]));
    if (empty($renderer)) {
      $renderer = $storage->load(implode('.', [$type, $bundle, 'default']));
    }
    $setting = $this->getSettings();
    $rows = [];
    foreach ($entities as $delta => $entity) {
      $table_entity = $renderer->build($entity);
      $paragraphs_id = $entity->get('id')->value;
      foreach ($fields as $field_name => $field) {
        $table_entity[$field_name]['#label_display'] = 'hidden';
        $value = render($table_entity[$field_name]);
        if (!empty($value)) {
          $notEmptyColumn[$field_name] = TRUE;
        }
        elseif (!empty($setting["empty_cell_value"])) {
          $value = $setting["empty_cell_value"];
        }
        $rows[$delta]['data'][$field_name] = $value;
        $field_type = $field->getType();

        //add data order for sort
        if ($setting['mode'] == 'datatables' &&
          in_array($field_type, [
            'timestamp',
            'datetime',
            'daterange',
            'datestamp',
            'smartdate',
            'number_decimal',
            'decimal',
            'number_float',
            'list_float',
          ]) &&
          !empty($table_entity[$field_name]["#items"])) {

          $list_value = $table_entity[$field_name]["#items"]->getValue();
          $value_order = $list_value[0]['value'];
          if (!is_numeric($value_order)) {
            $value_order = strtotime($value_order);
          }
          $rows[$delta]['data'][$field_name] = [
            'data' => $value,
            'data-order' => $value_order,
          ];
        }
      }
      //@todo reserver action for edit/duplicate/remove item
      $operation = $this->paragraphs_table_links_action($paragraphs_id);
      if (!empty($operation)) {
        $rows[$delta]['data']['operation'] = $operation;
      }

      $rows[$delta]['data-quickedit-entity-id'] = "$type/$paragraphs_id";
      $rows[$delta]["data-id"] = $paragraphs_id;
      $rows[$delta]["data-type"] = $type;
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   * Support table
   * https://datatables.net/
   * https://bootstrap-table.com/
   */
  public function getConfigurableViewModes() {
    return [
      'datatables' => $this->t('Datatables'),
      'bootstrapTable' => $this->t('Bootstrap Table'),
      'googleCharts' => $this->t('Google Charts'),
    ];
  }

  protected function paragraphs_table_links_action($paragraphsId = FALSE, $field_name = '', $lang = 'en', $delta = 0) {
    $route_params = [
      'entity_type' => 'paragraphs_item',
      'entity' => $paragraphsId,
      'field_name' => $field_name,
      'langcode' => $lang,
      'delta' => $delta,
    ];
    $operation = [
      '#type' => 'dropbutton',
      '#links' => [
        'view' => [
          'title' => $this->t('View'),
          'url' => Url::fromRoute('entity.paragraphs_item.canonical', $route_params),
        ],
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('entity.paragraphs_item.edit_form', $route_params),
        ],
        'duplicate' => [
          'title' => $this->t('Duplicate'),
          'url' => Url::fromRoute('paragraphs_item.add_page', $route_params),
        ],
        'delete' => [
          'title' => $this->t('Remove'),
          'url' => Url::fromRoute('entity.paragraphs_item.delete_form', $route_params),
        ],
      ],
    ];
    //@todo generate operation dropbutton with view/edit/delete/duplicate
    return FALSE;
  }

  public function bootstrapTableOption($header, $components, $langcode = 'en') {
    $data_option = [
      'toggle' => 'table',
      'search' => "true",
      'show-search-clear-button' => "true",
      'show-refresh' => "true",
      'show-toggle' => "true",
      'show-fullscreen' => "true",
      'show-columns' => "true",
      'show-columns-toggle-all' => "true",
      'show-export' => "true",
      'sortable' => "true",
      'click-to-select' => "true",
      'minimum-count-columns' => "2",
      'show-pagination-switch' => "true",
      'pagination' => "true",
      'page-list' => "[10, 25, 50, 100, all]",
      'show-footer' => "false",
    ];


    $languages = [
      'af' => 'af-ZA',
      'am' => 'am-ET',
      'ar' => 'ar-AE',
      'az' => 'az-Latn-AZ',
      'be' => 'be-BY',
      'bg' => 'bg-BG',
      'ca' => 'ca-ES',
      'cs' => 'cs-CZ',
      'cy' => 'cy-GB',
      'da' => 'da-DK',
      'de' => 'de-DE',
      'el' => 'el-GR',
      'eo' => 'eo-EO',
      'es' => 'es-ES',
      'et' => 'et-EE',
      'eu' => 'eu-EU',
      'fa' => 'fa-IR',
      'fi' => 'fi-fi',
      'fr' => 'fr-FR',
      'ga' => 'ga-IE',
      'gl' => 'gl-ES',
      'gu' => 'gu-IN',
      'he' => 'he-IL',
      'hi' => 'hi-IN',
      'hr' => 'hr-HR',
      'hu' => 'hu-HU',
      'hy' => 'hy-AM',
      'id' => 'id-ID',
      'is' => 'is-IS',
      'it' => 'it-CH',
      'ja' => 'ja-JP',
      'ka' => 'ka-GE',
      'kk' => 'kk-KZ',
      'km' => 'km-KH',
      'ko' => 'ko-KR',
      'ky' => 'ky-KG',
      'lo' => 'lo-LA',
      'lt' => 'lt-LT',
      'lv' => 'lv-LV',
      'mk' => 'mk-MK',
      'ml' => 'ml-IN',
      'mn' => 'mn-MN',
      'ne' => 'ne-NP',
      'nl' => 'nl-NL',
      'nb' => 'nb-NO',
      'nn' => 'nn-NO',
      'pa' => 'pa-IN',
      'pl' => 'pl-PL',
      'pt' => 'pt-PT',
      'ro' => 'ro-RO',
      'ru' => 'ru-RU',
      'si' => 'si-LK',
      'sk' => 'sk-SK',
      'sl' => 'sl-SI',
      'sq' => 'sq-AL',
      'sr' => 'sr-Latn-RS',
      'sv' => 'sv-SE',
      'sw' => 'sw-KE',
      'ta' => 'ta-IN',
      'te' => 'te-IN',
      'th' => 'th-TH',
      'tr' => 'tr-TR',
      'uk' => 'uk-UA',
      'ur' => 'ur-PK',
      'vi' => 'vn-VN',
      'fil' => 'fi-FI',
      'zh-hans' => 'zh-CN',
      'zh-hant' => 'zh-TW',
    ];
    if (!empty($languages[$langcode])) {
      $data_option['locale'] = $languages[$langcode];
    }
    return $data_option;
  }

  public function datatablesOption($header, $components, $langcode = 'en') {
    $datatable_options = [
      'bExpandable' => TRUE,
      'bInfo' => TRUE,
      'dom' => 'Bfrtip',
      "scrollX" => TRUE,
      'bStateSave' => FALSE,
      "ordering" => TRUE,
      'searching' => TRUE,
      'bMultiFilter' => FALSE,
      'bMultiFilter_position' => "header",
    ];
    foreach ($header as $field_name => $field_label) {
      $datatable_options['aoColumnHeaders'][] = $field_label;
      $column_options = [
        'name' => $field_name,
        'data' => $field_name,
        'orderable' => TRUE,
        'type' => 'html',
      ];

      // Attempt to autodetect the type of field in order to handle sortingcorrectly.
      if (in_array($components[$field_name]['type'], [
        'number_decimal',
        'number_integer',
        'number_float',
        'list_float',
        'list_integer',
      ])) {
        $column_options['type'] = 'html-num';
      }
      if (in_array($components[$field_name]['type'], [
        'datetime',
        'date',
        'datestamp',
      ])) {
        $column_options['type'] = 'date-fr';
      }
      $datatable_options['columns'][] = $column_options;
    }

    $languages = [
      'af' => 'Afrikaans',
      'am' => 'Amharic',
      'ar' => 'Arabic',
      'az' => 'Azerbaijan',
      'be' => 'Belarusian',
      'bg' => 'Bulgarian',
      'ca' => 'Catalan',
      'cs' => 'Czech',
      'cy' => 'Welsh',
      'da' => 'Danish',
      'de' => 'German',
      'el' => 'Greek',
      'eo' => 'Esperanto',
      'es' => 'Spanish',
      'et' => 'Estonian',
      'eu' => 'Basque',
      'fa' => 'Persian',
      'fi' => 'Finnish',
      'fr' => 'French',
      'ga' => 'Irish',
      'gl' => 'Galician',
      'gu' => 'Gujarati',
      'he' => 'Hebrew',
      'hi' => 'Hindi',
      'hr' => 'Croatian',
      'hu' => 'Hungarian',
      'hy' => 'Armenian',
      'id' => 'Indonesian',
      'is' => 'Icelandic',
      'it' => 'Italian',
      'ja' => 'Japanese',
      'ka' => 'Georgian',
      'kk' => 'Kazakh',
      'km' => 'Khmer',
      'ko' => 'Korean',
      'ku' => 'Kurdish',
      'ky' => 'Kyrgyz',
      'lo' => 'Lao',
      'lt' => 'Lithuanian',
      'lv' => 'Latvian',
      'mk' => 'Macedonian',
      'ml' => 'Malay',
      'mn' => 'Mongolian',
      'ne' => 'Nepali',
      'nl' => 'Dutch',
      'nb' => 'Norwegian-Bokmal',
      'nn' => 'Norwegian-Nynorsk',
      'pa' => 'Punjabi',
      'pl' => 'Polish',
      'pt' => 'Portuguese',
      'ro' => 'Romanian',
      'ru' => 'Russian',
      'si' => 'Sinhala',
      'sk' => 'Slovak',
      'sl' => 'Slovenian',
      'sq' => 'Albanian',
      'sr' => 'Serbian',
      'sv' => 'Swedish',
      'sw' => 'Swahili',
      'ta' => 'Tamil',
      'te' => 'telugu',
      'th' => 'Thai',
      'tr' => 'Turkish',
      'uk' => 'Ukrainian',
      'ur' => 'Urdu',
      'vi' => 'Vietnamese',
      'fil' => 'Filipino',
      'zh-hans' => 'Chinese',
      'zh-hant' => 'Chinese',
    ];
    if (!empty($languages[$langcode])) {
      $cdn_lang = '//cdn.datatables.net/plug-ins/';
      $version = '1.10.21';
      $language_url = $cdn_lang . $version . '/i18n/' . $languages[$langcode] . '.json';
      $datatable_options['language']['url'] = $language_url;
    }

    return $datatable_options;
  }

  private function googleChartsOption($option = FALSE) {
    $options = [
      'BarChart' => [
        'title' => $this->t('Bar'),
        'option' => [
          'bar' => ['groupWidth' => "95%"],
          'legend' => ['position' => "none"],
        ],
      ],
      'BubbleChart' => [
        'title' => $this->t('Bubble'),
        'option' => [
          'bubble' => ['textStyle' => ['fontSize' => 11]],
        ],
      ],
      'LineChart' => [
        'title' => $this->t('Line'),
        'option' => [
          'legend' => ['position' => "bottom"],
          'curveType' => 'function',
        ],
      ],
      'ColumnChart' => [
        'title' => $this->t('Column'),
        'option' => [
          'bar' => ['groupWidth' => "95%"],
          'legend' => ['position' => "none"],
        ],
      ],
      'ComboChart' => [
        'title' => $this->t('Combo'),
        'option' => [
          'seriesType' => 'bars',
        ],
      ],
      'PieChart' => [
        'title' => $this->t('Pie'),
        'option' => [
          'is3D' => TRUE,
        ],
      ],
      'ScatterChart' => [
        'title' => $this->t('Scatter'),
        'option' => [
          'legend' => ['position' => "none"],
        ],
      ],
      'SteppedAreaChart' => [
        'title' => $this->t('Stepped Area'),
        'option' => [
          'isStacked' => TRUE,
        ],
      ],
      'AreaChart' => [
        'title' => $this->t('Area'),
        'option' => [
          'legend' => ['position' => "top", 'maxLines' => 3],
          'isStacked' => 'relative',
        ],
      ],
      'Histogram' => [
        'title' => $this->t('Histogram'),
        'option' => [
          'legend' => ['position' => "top", 'maxLines' => 3],
          'interpolateNulls' => FALSE,
        ],
      ],
      'CandlestickChart' => [
        'title' => $this->t('Candlestick'),
        'option' => [
          'notHeader' => TRUE,
          'legend' => 'none',
          'bar' => ['groupWidth' => '100%'],
        ],
      ],
    ];
    if ($option) {
      return $options[$option]['option'];
    }
    $titleOptions = [];
    foreach ($options as $type => $option) {
      $titleOptions[$type] = $option['title'];
    }
    return $titleOptions;
  }
}
