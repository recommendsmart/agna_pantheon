<?php

namespace Drupal\media_entity_lottie\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileMediaFormatterBase;

/**
 * Plugin implementation of the 'file_lottie_player' formatter.
 *
 * @see: https://github.com/LottieFiles/lottie-player
 *
 * @FieldFormatter(
 *   id = "file_lottie_player",
 *   label = @Translation("Lottie player"),
 *   description = @Translation("Display the file using an HTML5 lottie-player tag."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileLottiePlayerFormatter extends FileMediaFormatterBase {

  /**
   * {@inheritdoc}
   *
   * The MIME media type for JSON text is application/json.
   */
  public static function getMediaType() {
    return 'application';
  }

  /**
   * {@inheritdoc}
   */
  protected function getHtmlTag() {
    return 'lottie-player';
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if (!parent::isApplicable($field_definition)) {
      return FALSE;
    }

    // Calling the parent::isApplicable is not enough. There are several
    // extensions (ej. audio formats) with the same MIME type as a json file.
    if (strpos($field_definition->getName(), 'field_media_lottie_file') !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'background' => '#FFFFFF',
      'hover' => FALSE,
      'mode' => 'normal',
      'speed' => 1,
      'count' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Lottie player does not support source files.
    $elements['multiple_file_display_type']['#attributes']['disabled'] = 'disabled';

    $elements['background'] = [
      '#type' => 'color',
      '#title' => $this->t('Background'),
      '#description' => $this->t('Background color.'),
      '#default_value' => $this->getSetting('background'),
      '#required' => TRUE,
    ];
    $elements['hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hover'),
      '#description' => $this->t('Whether to play on mouse hover.'),
      '#default_value' => $this->getSetting('hover'),
    ];
    $elements['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#description' => $this->t('Play mode.'),
      '#options' => [
        'normal' => $this->t('Normal'),
        'bounce ' => $this->t('Bounce'),
      ],
      '#default_value' => $this->getSetting('mode'),
      '#required' => TRUE,
    ];
    $elements['speed'] = [
      '#type' => 'number',
      '#title' => t('Speed'),
      '#description' => $this->t('Animation speed.'),
      '#min' => 1,
      '#default_value' => $this->getSetting('speed'),
      '#required' => TRUE,
    ];
    $elements['count'] = [
      '#type' => 'number',
      '#title' => t('Count'),
      '#description' => $this->t('Number of times to loop animation. Set 0 to set undefined.'),
      '#min' => 0,
      '#default_value' => $this->getSetting('count'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Background: %background',
      ['%background' => $this->getSetting('background')]
    );
    $summary[] = $this->t('Hover: %hover',
      ['%hover' => $this->getSetting('hover') ? $this->t('yes') : $this->t('no')]
    );
    $summary[] = $this->t('Mode: %mode',
      ['%mode' => 'PlayMode.' . ucwords($this->getSetting('mode'))]
    );
    $summary[] = $this->t('Speed: %speed',
      ['%speed' => ($this->getSetting('speed') === 1) ? $this->t('normal') : $this->getSetting('speed')]
    );
    $summary[] = $this->t('Count: %count',
      ['%count' => ($this->getSetting('count') < 1) ? 'undefined' : $this->getSetting('count')]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAttributes(array $additional_attributes = []) {
    $attributes = parent::prepareAttributes();
    $attributes->setAttribute('background', $this->getSetting('background'));
    $attributes->setAttribute('hover', $this->getSetting('hover'));

    if ($this->getSetting('mode') !== 'normal') {
      $attributes->setAttribute('mode', $this->getSetting('mode'));
    }

    if ($this->getSetting('speed') > 1) {
      $attributes->setAttribute('speed', $this->getSetting('speed'));
    }

    if ($this->getSetting('count') > 0) {
      $attributes->setAttribute('count', $this->getSetting('count'));
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $attributes = $this->prepareAttributes();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $attributes->setAttribute('src', $file->createFileUrl());
      $elements[$delta] = [
        '#theme' => $this->getPluginId(),
        '#attributes' => $attributes,
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }
    $elements['#attached'] = [
      'library' => [
        'media_entity_lottie/lottie_player',
      ],
    ];

    return $elements;
  }

}
