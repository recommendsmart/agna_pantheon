<?php

namespace Drupal\entity_reference_views\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Plugin implementation of the 'entity_reference_views_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_views_autocomplete",
 *   label = @Translation("Autocomplete (Views)"),
 *   description = @Translation("An autocomplete text field with views to
 *   render."), field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AutocompleteViewsWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'view' => NULL,
        'is_active' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['is_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display corresponding view'),
      '#default_value' => !empty($this->getSetting('is_active')),
    ];

    $element['view'] = [
      '#type' => 'select',
      '#options' => $this->getViewsList(),
      '#title' => $this->t('View'),
      '#default_value' => $this->getSetting('view'),
      '#states' => [
        'visible' => [
          ':input[name="fields[offer_group][settings_edit_form][settings][is_active]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if (empty($this->getSetting('is_active'))) {
      $summary[] = t('View display is not active');
      return $summary;
    }

    $summary[] = t('View display is active');
    $view_id = $this->getSetting('view');

    if (empty($view_id)) {
      $summary[] = t('No view');
      return $summary;
    }

    $summary[] = t('View: @view', ['@view' => $view_id]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (empty($this->getSetting('is_active'))) {
      return $element;
    }

    $elements = [
      'target_id' => $element,
    ];

    if (!empty($referenced_entities[$delta]) && !empty($this->getSetting('view'))) {
      [$view_id, $display_id] = explode(':', $this->getSetting('view'));
      $view = Views::getView($view_id);
      if (!$view) {
        return $elements;
      }
      $args = [$referenced_entities[$delta]->id()];
      $elements['table'] = [
        '#type' => 'view',
        '#name' => $view->storage->id(),
        '#display_id' => $display_id,
        '#arguments' => $args,
        '#embed' => TRUE,
        '#view' => $view,
      ];
    }

    return $elements;
  }

  protected function getViewsList() {
    $entity_type_id = $this->getFieldSetting('target_type');

    $displays = Views::getApplicableViews('entity_reference_field_display');

    // Filter views that list the entity type we want, and group the separate
    // displays by view.
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
    $view_storage = $this->entityTypeManager()->getStorage('view');

    $options = [];
    foreach ($displays as $data) {
      [$view_id, $display_id] = $data;
      $view = $view_storage->load($view_id);
      if (in_array($view->get('base_table'), [
        $entity_type->getBaseTable(),
        $entity_type->getDataTable(),
      ])) {
        $display = $view->get('display');
        $options[$view_id . ':' . $display_id] = $view_id . ' - ' . $display[$display_id]['display_title'];
      }
    }
    return $options;
  }

  /**
   * Provides entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity type manager.
   */
  protected function entityTypeManager() {
    return \Drupal::entityTypeManager();
  }

}
