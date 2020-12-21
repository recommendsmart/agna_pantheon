<?php

namespace Drupal\burndown\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Field formatter "burndown_log_default".
 *
 * @FieldFormatter(
 *   id = "burndown_log_default",
 *   label = @Translation("Burndown Log default"),
 *   field_types = {
 *     "burndown_log",
 *   }
 * )
 */
class BurndownLogDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $output = [];

    foreach ($items as $delta => $item) {

      $build = [];

      $build['type'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['type'],
        ],
        'label' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__label'],
          ],
          '#markup' => t('Log Type'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => $item->type,
        ],
      ];

      $build['created'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['burndown_log__created'],
        ],
        'label' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__label'],
          ],
          '#markup' => t('Date'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => \Drupal::service('date.formatter')->format($item->created),
        ],
      ];

      $user = User::load($item->uid);

      $build['uid'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['burndown_log__created'],
        ],
        'label' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__label'],
          ],
          '#markup' => t('User'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => isset($user) ? $user->getDisplayName() : '',
        ],
      ];

      $build['comment'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['burndown_log__comment'],
        ],
        'label' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__label'],
          ],
          '#markup' => t('Comment'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => $item->comment,
        ],
      ];

      $build['work_done'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['burndown_log__work_down'],
        ],
        'label' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__label'],
          ],
          '#markup' => t('Work Done'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => $item->work_done,
        ],
      ];

      $build['description'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['burndown_log__description'],
        ],
        'label' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__label'],
          ],
          '#markup' => t('Description'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => $item->description,
        ],
      ];

      $output[$delta] = $build;
    }

    return $output;
  }

}
