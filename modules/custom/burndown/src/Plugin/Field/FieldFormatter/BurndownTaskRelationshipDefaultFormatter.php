<?php

namespace Drupal\burndown\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\burndown\Entity\Task;

/**
 * Field formatter "burndown_task_relationship_default".
 *
 * @FieldFormatter(
 *   id = "burndown_task_relationship_default",
 *   label = @Translation("Burndown Task Relationship default"),
 *   field_types = {
 *     "burndown_task_relationship",
 *   }
 * )
 */
class BurndownTaskRelationshipDefaultFormatter extends FormatterBase {

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

      $task = Task::load($item->task_id);

      $build['task_id'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['burndown_task_relationship__task_id'],
        ],
        'label' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__label'],
          ],
          '#markup' => t('Task'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => isset($task) ? $task->getTicketID() : '',
        ],
      ];

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
          '#markup' => t('Relationship Type'),
        ],
        'value' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field__item'],
          ],
          '#plain_text' => $item->type,
        ],
      ];

      $output[$delta] = $build;
    }

    return $output;
  }

}
