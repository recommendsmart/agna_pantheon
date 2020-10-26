<?php

namespace Drupal\rocketship_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs_asymmetric_translation_widgets\Plugin\Field\FieldWidget\ParagraphsAsymmetricWidget;

/**
 * Plugin implementation of the 'paragraphs_asymmetric' widget.
 *
 * @FieldWidget(
 *   id = "rs_paragraphs_asymmetric",
 *   label = @Translation("RS Paragraphs EXPERIMENTAL Asymmetric"),
 *   description = @Translation("An experimental paragraphs inline form widget
 *   that supports asymmetric translations."), field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 *
 * IMPORTANT! This widget requires patching paragraphs_asymmetric_translation_widgets.
 * Missing patch will cause fatal PHP error.
 *
 * @see https://www.drupal.org/project/paragraphs_asymmetric_translation_widgets/issues/2959900
 * @see https://www.drupal.org/project/drupal/issues/3009016#comment-12828375
 *
 * Make sure the following two patches are applied. If not, add this snippet
 * to your top-level composer.json file:
 *
 * @code
 * "extra": {
 *   "patches": {
 *     "drupal/paragraphs_asymmetric_translation_widgets": {
 *       "Add support for experimental widget -- https://www.drupal.org/project/paragraphs_asymmetric_translation_widgets/issues/2959900": "https://www.drupal.org/files/issues/2018-11-12/2959900-13.patch"
 *     },
 *     "drupal/core": {
 *       "Incorrect translation status for duplicated entities. -- https://www.drupal.org/project/drupal/issues/3009016#comment-12828375": "https://www.drupal.org/files/issues/2018-10-25/3009016-6.patch"
 *     }
 *   }
 * }
 * @endcode
 */
class RSParagraphsAsymmetricWidget extends ParagraphsAsymmetricWidget {

  /**
   * {@inheritdoc}
   */
  protected function buildButtonsAddMode() {
    $options = $this->getAccessibleOptions();
    $add_mode = $this->getSetting('add_mode');
    $paragraphs_type_storage = \Drupal::entityTypeManager()
      ->getStorage('paragraphs_type');

    // Build the buttons.
    $add_more_elements = [];
    foreach ($options as $machine_name => $label) {
      $button_key = 'add_more_button_' . $machine_name;
      $add_more_elements[$button_key] = $this->expandButton([
        '#type' => 'submit',
        '#name' => $this->fieldIdPrefix . '_' . $machine_name . '_add_more',
        '#value' => $add_mode == 'modal' ? $label : $this->t('Add @type', ['@type' => $label]),
        '#attributes' => [
          'class' => ['field-add-more-submit'],
          'aria-describedby' => [$this->fieldIdPrefix . '_' . $machine_name . '_add_more-description'],
        ],
        '#limit_validation_errors' => [
          array_merge($this->fieldParents, [
            $this->fieldDefinition->getName(),
            'add_more',
          ]),
        ],
        '#submit' => [[get_class($this), 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'addMoreAjax'],
          'wrapper' => $this->fieldWrapperId,
        ],
        '#bundle_machine_name' => $machine_name,
        '#prefix' => '',
      ]);

      if ($add_mode === 'modal') {
        if ($icon_file = $paragraphs_type_storage->load($machine_name)
          ->getIconFile()) {
          $icon_url = file_create_url($icon_file->getFileUri());
          $add_more_elements[$button_key]['#prefix'] .= "<i class=\"paragraphs-add-dialog-row__icon\"><img src=\"$icon_url\" /></i>";
        }
        else {
          $add_more_elements[$button_key]['#prefix'] .= "<i class=\"paragraphs-add-dialog-row__icon\"></i>";
        }

        if ($desc = $paragraphs_type_storage->load($machine_name)
          ->getDescription()) {
          $add_more_elements[$button_key]['#prefix'] .= "<div class=\"paragraphs-add-dialog-row__description\">";
          $add_more_elements[$button_key]['#prefix'] .= "<i class=\"paragraphs-add-dialog-row__description__icon\"></i>";
          $add_more_elements[$button_key]['#prefix'] .= "<div class=\"paragraphs-add-dialog-row__description__content\" role=\"tooltip\" aria-hidden=\"true\" id=\"" . $this->fieldIdPrefix . "_" . $machine_name . "_add_more" . "-description\">$desc</div>";
          $add_more_elements[$button_key]['#prefix'] .= "</div>";
        }
        else {
          $add_more_elements[$button_key]['#prefix'] .= "<div class=\"paragraphs-add-dialog-row__description\"></div>";
        }
      }
    }

    // Determine if buttons should be rendered as dropbuttons.
    if (count($options) > 1 && $add_mode == 'dropdown') {
      $add_more_elements = $this->buildDropbutton($add_more_elements);
      $add_more_elements['#suffix'] = $this->t('to %type', ['%type' => $this->fieldDefinition->getLabel()]);
    }
    elseif ($add_mode == 'modal') {
      $this->buildModalAddForm($add_more_elements);
      $add_more_elements['add_modal_form_area']['#suffix'] = $this->t('to %type', ['%type' => $this->fieldDefinition->getLabel()]);
    }
    $add_more_elements['#weight'] = 1;

    $add_more_elements['#attached']['library'][] = 'rocketship_core/expanded_asymmetric_paragraphs';

    return $add_more_elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Add the description for the paragraph.
    $paragraphs_type = \Drupal::entityTypeManager()
      ->getStorage('paragraphs_type')->load($element['#paragraph_type']);

    $element['top']['summary']['description'] = [
      '#markup' => '<div class="paragraph-type-description">' . $paragraphs_type->getDescription() . '</div>',
      '#weight' => 1,
    ];

    return $element;
  }

}
