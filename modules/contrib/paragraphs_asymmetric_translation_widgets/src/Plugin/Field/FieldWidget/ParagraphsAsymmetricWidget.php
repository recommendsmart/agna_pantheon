<?php

namespace Drupal\paragraphs_asymmetric_translation_widgets\Plugin\Field\FieldWidget;

use Drupal\field_group\FormatterHelper;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Plugin implementation of the 'paragraphs_asymmetric' widget.
 *
 * @FieldWidget(
 *   id = "paragraphs_asymmetric",
 *   label = @Translation("Paragraphs EXPERIMENTAL Asymmetric"),
 *   description = @Translation("An experimental paragraphs inline form widget that supports asymmetric translations."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsAsymmetricWidget extends ParagraphsWidget {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\content_translation\Controller\ContentTranslationController::prepareTranslation()
   *   Uses a similar approach to populate a new translation.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraphs_entity */
    $paragraphs_entity = NULL;
    $host = $items->getEntity();
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $entity_type_manager = \Drupal::entityTypeManager();
    $target_type = $this->getFieldSetting('target_type');

    $item_mode = isset($widget_state['paragraphs'][$delta]['mode']) ? $widget_state['paragraphs'][$delta]['mode'] : 'edit';
    $default_edit_mode = $this->getSetting('edit_mode');

    $closed_mode_setting = isset($widget_state['closed_mode']) ? $widget_state['closed_mode'] : $this->getSetting('closed_mode');
    $autocollapse_setting = isset($widget_state['autocollapse']) ? $widget_state['autocollapse'] : $this->getSetting('autocollapse');

    $show_must_be_saved_warning = !empty($widget_state['paragraphs'][$delta]['show_warning']);

    if (isset($widget_state['paragraphs'][$delta]['entity'])) {
      $paragraphs_entity = $widget_state['paragraphs'][$delta]['entity'];
    }
    elseif (isset($items[$delta]->entity)) {
      $paragraphs_entity = $items[$delta]->entity;

      // We don't have a widget state yet, get from selector settings.
      if (!isset($widget_state['paragraphs'][$delta]['mode'])) {

        if ($default_edit_mode == 'open') {
          $item_mode = 'edit';
        }
        elseif ($default_edit_mode == 'closed') {
          $item_mode = 'closed';
        }
      }
    }
    elseif (isset($widget_state['selected_bundle'])) {

      $entity_type = $entity_type_manager->getDefinition($target_type);
      $bundle_key = $entity_type->getKey('bundle');

      $paragraphs_entity = $entity_type_manager->getStorage($target_type)->create([
        $bundle_key => $widget_state['selected_bundle'],
      ]);
      $paragraphs_entity->setParentEntity($host, $field_name);

      $item_mode = 'edit';
    }

    if ($paragraphs_entity) {
      $paragraphs_entity = $this->prepareEntity($paragraphs_entity, $items, $form_state);

      $element_parents = $parents;
      $element_parents[] = $field_name;
      $element_parents[] = $delta;
      $element_parents[] = 'subform';

      $id_prefix = implode('-', array_merge($parents, [$field_name, $delta]));
      $wrapper_id = Html::getUniqueId($id_prefix . '-item-wrapper');

      $element += [
        '#type' => 'container',
        '#element_validate' => [[$this, 'elementValidate']],
        '#paragraph_type' => $paragraphs_entity->bundle(),
        'subform' => [
          '#type' => 'container',
          '#parents' => $element_parents,
        ],
      ];

      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';

      // Create top section structure with all needed subsections.
      $element['top'] = [
        '#type' => 'container',
        '#weight' => -1000,
        '#attributes' => [
          'class' => [
            'paragraph-top',
            // Add a flag to indicate if the add_above feature is enabled and
            // should be injected client-side.
            $this->isFeatureEnabled('add_above') ? 'add-above-on' : 'add-above-off',
          ],
        ],
        // Section for paragraph type information.
        'type' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['paragraph-type']],
        ],
        // Section for info icons.
        'icons' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['paragraph-info']],
        ],
        'summary' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['paragraph-summary']],
        ],
        // Paragraphs actions element for actions and dropdown actions.
        'actions' => [
          '#type' => 'paragraphs_actions',
        ],
      ];
      // Holds information items.
      $info = [];

      $item_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);
      if (isset($item_bundles[$paragraphs_entity->bundle()])) {
        $bundle_info = $item_bundles[$paragraphs_entity->bundle()];

        $element['top']['type']['label'] = ['#markup' => $bundle_info['label']];

        // Type icon and label bundle.
        if ($icon_url = $paragraphs_entity->type->entity->getIconUrl()) {
          $element['top']['type']['icon'] = [
            '#theme' => 'image',
            '#uri' => $icon_url,
            '#attributes' => [
              'class' => ['paragraph-type-icon'],
              'title' => $bundle_info['label'],
            ],
            '#weight' => 0,
            // We set inline height and width so icon don't resize on first load
            // while CSS is still not loaded.
            '#height' => 16,
            '#width' => 16,
          ];
        }
        $element['top']['type']['label'] = [
          '#markup' => '<span class="paragraph-type-label">' . $bundle_info['label'] . '</span>',
          '#weight' => 1,
        ];

        // Widget actions.
        $widget_actions = [
          'actions' => [],
          'dropdown_actions' => [],
        ];

        $widget_actions['dropdown_actions']['duplicate_button'] = [
          '#type' => 'submit',
          '#value' => $this->t('Duplicate'),
          '#name' => $id_prefix . '_duplicate',
          '#weight' => 502,
          '#submit' => [[get_class($this), 'duplicateSubmit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name, $delta])],
          '#delta' => $delta,
          '#ajax' => [
            'callback' => [get_class($this), 'itemAjax'],
            'wrapper' => $widget_state['ajax_wrapper_id'],
          ],
          '#access' => $this->duplicateButtonAccess($paragraphs_entity),
        ];

        if ($item_mode != 'remove') {
          $widget_actions['dropdown_actions']['remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => $id_prefix . '_remove',
            '#weight' => 501,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            // Ignore all validation errors because deleting invalid paragraphs
            // is allowed.
            '#limit_validation_errors' => [],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
            ],
            '#access' => $this->removeButtonAccess($paragraphs_entity),
            '#paragraphs_mode' => 'remove',
          ];
        }

        if ($item_mode == 'edit') {
          if (isset($paragraphs_entity)) {
            $widget_actions['actions']['collapse_button'] = [
              '#value' => $this->t('Collapse'),
              '#name' => $id_prefix . '_collapse',
              '#weight' => 1,
              '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
              '#limit_validation_errors' => [array_merge($parents, [$field_name, $delta])],
              '#delta' => $delta,
              '#ajax' => [
                'callback' => [get_class($this), 'itemAjax'],
                'wrapper' => $widget_state['ajax_wrapper_id'],
              ],
              '#access' => $paragraphs_entity->access('update'),
              '#paragraphs_mode' => 'closed',
              '#paragraphs_show_warning' => TRUE,
              '#attributes' => [
                'class' => ['paragraphs-icon-button', 'paragraphs-icon-button-collapse'],
                'title' => $this->t('Collapse'),
              ],
            ];
          }
        }
        else {
          $widget_actions['actions']['edit_button'] = $this->expandButton([
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => $id_prefix . '_edit',
            '#weight' => 1,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [
              array_merge($parents, [$field_name, $delta]),
            ],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
            ],
            '#access' => $paragraphs_entity->access('update'),
            '#paragraphs_mode' => 'edit',
            '#attributes' => [
              'class' => ['paragraphs-icon-button', 'paragraphs-icon-button-edit'],
              'title' => $this->t('Edit'),
            ],
          ]);

          if ($show_must_be_saved_warning && $paragraphs_entity->isChanged()) {
            $info['changed'] = [
              '#theme' => 'paragraphs_info_icon',
              '#message' => $this->t('You have unsaved changes on this @title item.', ['@title' => $this->getSetting('title')]),
              '#icon' => 'changed',
            ];
          }

          if (!$paragraphs_entity->access('view')) {
            $info['preview'] = [
              '#theme' => 'paragraphs_info_icon',
              '#message' => $this->t('You are not allowed to view this @title.', ['@title' => $this->getSetting('title')]),
              '#icon' => 'view',
            ];
          }
        }

        // If update is disabled we will show lock icon in actions section.
        if (!$paragraphs_entity->access('update')) {
          $widget_actions['actions']['edit_disabled'] = [
            '#theme' => 'paragraphs_info_icon',
            '#message' => $this->t('You are not allowed to edit or remove this @title.', ['@title' => $this->getSetting('title')]),
            '#icon' => 'lock',
            '#weight' => 1,
          ];
        }

        if (!$paragraphs_entity->access('update') && $paragraphs_entity->access('delete')) {
          $info['edit'] = [
            '#theme' => 'paragraphs_info_icon',
            '#message' => $this->t('You are not allowed to edit this @title.', ['@title' => $this->getSetting('title')]),
            '#icon' => 'edit-disabled',
          ];
        }
        elseif (!$paragraphs_entity->access('delete') && $paragraphs_entity->access('update')) {
          $info['remove'] = [
            '#theme' => 'paragraphs_info_icon',
            '#message' => $this->t('You are not allowed to remove this @title.', ['@title' => $this->getSetting('title')]),
            '#icon' => 'delete-disabled',
          ];
        }

        $context = [
          'form' => $form,
          'widget' => self::getWidgetState($parents, $field_name, $form_state, $widget_state),
          'items' => $items,
          'delta' => $delta,
          'element' => $element,
          'form_state' => $form_state,
          'paragraphs_entity' => $paragraphs_entity,
          'is_translating' => $this->isTranslating,
          'allow_reference_changes' => $this->allowReferenceChanges(),
        ];

        // Allow modules to alter widget actions.
        \Drupal::moduleHandler()->alter('paragraphs_widget_actions', $widget_actions, $context);

        if (count($widget_actions['actions'])) {
          // Expand all actions to proper submit elements and add it to top
          // actions sub component.
          $element['top']['actions']['actions'] = array_map([$this, 'expandButton'], $widget_actions['actions']);
        }

        if (count($widget_actions['dropdown_actions'])) {
          // Expand all dropdown actions to proper submit elements and add
          // them to top dropdown actions sub component.
          $element['top']['actions']['dropdown_actions'] = array_map([$this, 'expandButton'], $widget_actions['dropdown_actions']);
        }
      }

      $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));

      // @todo Remove as part of https://www.drupal.org/node/2640056
      if (\Drupal::moduleHandler()->moduleExists('field_group')) {
        $context = [
          'entity_type' => $paragraphs_entity->getEntityTypeId(),
          'bundle' => $paragraphs_entity->bundle(),
          'entity' => $paragraphs_entity,
          'context' => 'form',
          'display_context' => 'form',
          'mode' => $display->getMode(),
        ];

        field_group_attach_groups($element['subform'], $context);
        if (method_exists(FormatterHelper::class, 'formProcess')) {
          $element['subform']['#process'][] = [FormatterHelper::class, 'formProcess'];
        }
        elseif (function_exists('field_group_form_pre_render')) {
          $element['subform']['#pre_render'][] = 'field_group_form_pre_render';
        }
        elseif (function_exists('field_group_form_process')) {
          $element['subform']['#process'][] = 'field_group_form_process';
        }
      }

      if ($item_mode === 'edit') {
        $display->buildForm($paragraphs_entity, $element['subform'], $form_state);
        $hide_untranslatable_fields = $paragraphs_entity->isDefaultTranslationAffectedOnly();

        foreach (Element::children($element['subform']) as $field) {
          if ($paragraphs_entity->hasField($field)) {
            $field_definition = $paragraphs_entity->get($field)->getFieldDefinition();

            // Do a check if we have to add a class to the form element. We need
            // those classes (paragraphs-content and paragraphs-behavior) to show
            // and hide elements, depending of the active perspective.
            // We need them to filter out entity reference revisions fields that
            // reference paragraphs, cause otherwise we have problems with showing
            // and hiding the right fields in nested paragraphs.
            $is_paragraph_field = FALSE;
            if ($field_definition->getType() === 'entity_reference_revisions') {
              // Check if we are referencing paragraphs.
              if ($field_definition->getSetting('target_type') === 'paragraph') {
                $is_paragraph_field = TRUE;
              }
            }

            if (!$is_paragraph_field) {
              $element['subform'][$field]['#attributes']['class'][] = 'paragraphs-content';
            }
            $translatable = $field_definition->isTranslatable();
            // Hide untranslatable fields when configured to do so except
            // paragraph fields.
            if (!$translatable && $this->isTranslating && !$is_paragraph_field) {
              if ($hide_untranslatable_fields) {
                $element['subform'][$field]['#access'] = FALSE;
              }
              else {
                $element['subform'][$field]['widget']['#after_build'][] = [
                  static::class,
                  'addTranslatabilityClue',
                ];
              }
            }
          }
        }

        // Build the behavior plugins fields.
        $paragraphs_type = $paragraphs_entity->getParagraphType();
        if ($paragraphs_type && \Drupal::currentUser()->hasPermission('edit behavior plugin settings')) {
          $element['behavior_plugins']['#weight'] = -99;
          foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin) {
            $element['behavior_plugins'][$plugin_id] = [
              '#type' => 'container',
              '#group' => implode('][', array_merge($element_parents, ['paragraph_behavior'])),
            ];
            $subform_state = SubformState::createForSubform($element['behavior_plugins'][$plugin_id], $form, $form_state);
            if ($plugin_form = $plugin->buildBehaviorForm($paragraphs_entity, $element['behavior_plugins'][$plugin_id], $subform_state)) {
              $element['behavior_plugins'][$plugin_id] = $plugin_form;
              // Add the paragraphs-behavior class, so that we are able to show
              // and hide behavior fields, depending on the active perspective.
              $element['behavior_plugins'][$plugin_id]['#attributes']['class'][] = 'paragraphs-behavior';
            }
          }
        }
      }
      elseif ($item_mode == 'closed') {
        $element['subform'] = [];
        $element['behavior_plugins'] = [];
        if ($closed_mode_setting === 'preview') {
          // The closed paragraph is displayed as a rendered preview.
          $view_builder = $entity_type_manager->getViewBuilder('paragraph');

          $element['preview'] = $view_builder->view($paragraphs_entity, 'preview', $paragraphs_entity->language()->getId());
          $element['preview']['#access'] = $paragraphs_entity->access('view');
        }
        else {
          // The closed paragraph is displayed as a summary.
          if ($paragraphs_entity) {
            $summary = $paragraphs_entity->getSummary();
            if (!empty($summary)) {
              $element['top']['summary']['fields_info'] = [
                '#markup' => $summary,
                '#prefix' => '<div class="paragraphs-collapsed-description">',
                '#suffix' => '</div>',
                '#access' => $paragraphs_entity->access('update') || $paragraphs_entity->access('view'),
              ];
            }

            $info = array_merge($info, $paragraphs_entity->getIcons());
          }
        }
      }
      else {
        $element['subform'] = [];
      }

      // If we have any info items lets add them to the top section.
      if (count($info)) {
        foreach ($info as $info_item) {
          if (!isset($info_item['#access']) || $info_item['#access']) {
            $element['top']['icons']['items'] = $info;
            break;
          }
        }
      }

      $element['subform']['#attributes']['class'][] = 'paragraphs-subform';
      $element['subform']['#access'] = $paragraphs_entity->access('update');

      if ($item_mode == 'remove') {
        $element['#access'] = FALSE;
      }

      $widget_state['paragraphs'][$delta]['entity'] = $paragraphs_entity;
      $widget_state['paragraphs'][$delta]['display'] = $display;
      $widget_state['paragraphs'][$delta]['mode'] = $item_mode;
      $widget_state['closed_mode'] = $closed_mode_setting;
      $widget_state['autocollapse'] = $autocollapse_setting;
      $widget_state['autocollapse_default'] = $this->getSetting('autocollapse');

      static::setWidgetState($parents, $field_name, $form_state, $widget_state);
    }
    else {
      $element['#access'] = FALSE;
    }

    return $element;
  }

  /**
   * Prepares the paragraph entity for translation.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $entity
   *   The paragraph entity.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items list that hosts this paragraph.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   The prepared paragraph.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::initFormLangcodes()
   */
  protected function prepareEntity(Paragraph $entity, FieldItemListInterface $items, FormStateInterface $form_state) {
    // Detect if we are translating.
    $this->initIsTranslating($form_state, $items->getEntity());
    $langcode = $form_state->get('langcode');

    if (!$this->isTranslating) {
      // Set the langcode if we are not translating.
      $langcode_key = $entity->getEntityType()->getKey('langcode');
      if ($entity->get($langcode_key)->value != $langcode) {
        // If a translation in the given language already exists, switch to
        // that. If there is none yet, update the language.
        if ($entity->hasTranslation($langcode)) {
          $entity = $entity->getTranslation($langcode);
        }
        else {
          $entity->set($langcode_key, $langcode);
        }
      }
    }

    // Localised Paragraphs.
    //  If the parent field is marked as translatable, assume paragraphs
    //  to be localized (host entity expects different paragraphs for
    //  different languages)
    elseif ($items->getFieldDefinition()->isTranslatable()) {
      if (!empty($form_state->get('content_translation'))) {
        $entity = $this->createDuplicateWithSingleLanguage($entity, $langcode);
      }
      if ($entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
      $entity->setOwnerId(\Drupal::currentUser()->id());
      $entity->setRevisionAuthorId(\Drupal::currentUser()->id());
    }

    // Translated Paragraphs
    //  If the parent field is not translatable, assume the paragraph
    //  entity itself (rather the fields within it) are marked as
    //  translatable. (host entity expects same paragraphs in different
    //  languages).
    else {
      // Add translation if missing for the target language.
      if (!$entity->hasTranslation($langcode)) {
        // Get the selected translation of the paragraph entity.
        $entity_langcode = $entity->language()->getId();
        $source = $form_state->get(['content_translation', 'source']);
        $source_langcode = $source ? $source->getId() : $entity_langcode;
        $entity = $entity->getTranslation($source_langcode);
        // The paragraphs entity has no content translation source field if
        // no paragraph entity field is translatable, even if the host is.
        if ($entity->hasField('content_translation_source')) {
          // Initialise the translation with source language values.
          $entity->addTranslation($langcode, $entity->toArray());
          $translation = $entity->getTranslation($langcode);
          $manager = \Drupal::service('content_translation.manager');
          $manager->getTranslationMetadata($translation)
            ->setSource($entity->language()->getId());
        }
      }
      // If any paragraphs type is translatable do not switch.
      if ($entity->hasField('content_translation_source')) {
        // Switch the paragraph to the translation.
        $entity = $entity->getTranslation($langcode);
      }
    }

    return $entity;
  }

  /**
   * Checks if we can allow reference changes.
   *
   * @return bool
   *   TRUE if we can allow reference changes, otherwise FALSE.
   */
  protected function allowReferenceChanges() {
    return !$this->isTranslating || $this->fieldDefinition->isTranslatable();
  }

  /**
   * Clones a paragraph recursively.
   *
   * Also, in case of a translatable paragraph, updates its original language
   * and removes all other translations.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to clone.
   * @param string $langcode
   *   Language code for all the clone entities created.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   New paragraph object with the data from the original paragraph. Not
   *   saved. All sub-paragraphs are clones as well.
   */
  protected function createDuplicateWithSingleLanguage(ParagraphInterface $paragraph, $langcode) {
    $duplicate = $paragraph->createDuplicate();

    // Clone all sub-paragraphs recursively.
    foreach ($duplicate->getFields(FALSE) as $field) {
      // @todo: should we support field collections as well?
      if ($field->getFieldDefinition()->getType() == 'entity_reference_revisions' && $field->getFieldDefinition()->getTargetEntityTypeId() == 'paragraph') {
        foreach ($field as $item) {
          $item->entity = $this->createDuplicateWithSingleLanguage($item->entity, $langcode);
        }
      }
    }

    // Change the original language and remove possible translations.
    if ($duplicate->isTranslatable()) {
      // If there is already is a translation for the language we want to set as
      // default, we have to remove it. This should never happen, but since
      // there are several unresolved issues related to orphaned paragraphs, we
      // need to make sure our widget does not break because of this.
      if (isset($duplicate->getTranslationLanguages(FALSE)[$langcode])) {
        $duplicate->removeTranslation($langcode);
      }
      $duplicate->set('langcode', $langcode);
      foreach ($duplicate->getTranslationLanguages(FALSE) as $language) {
        try {
          $duplicate->removeTranslation($language->getId());
        }
        catch (\InvalidArgumentException $e) {
          // Should never happen.
        }
      }
    }

    return $duplicate;
  }

}
