<?php

namespace Drupal\burndown\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\burndown\Entity\Task;

/**
 * Field widget "burndown_task_relationship_default".
 *
 * @FieldWidget(
 *   id = "burndown_task_relationship_default",
 *   label = @Translation("Burndown Task Relationship default"),
 *   field_types = {
 *     "burndown_task_relationship",
 *   }
 * )
 */
class BurndownTaskRelationshipDefaultWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // $item is where the current saved values are stored.
    $item =& $items[$delta];

    // $element is already populated with #title, #description, #delta,
    // #required, #field_parents, etc.
    $element += [
      '#type' => 'fieldset',
    ];

    // Load the task.
    if (isset($item->task_id)) {
      $task = Task::load($item->task_id);
    }

    // The other task.
    $element['task_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Task'),
      '#target_type' => 'burndown_task',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'burndown_task_by_ticket_id',
          'display_name' => 'entity_reference_1',
          'arguments' => []
        ], 
        'match_operator' => 'CONTAINS'
      ],
      '#default_value' => isset($item->task_id) ? $task : NULL,
    ];

    // Relationship type.
    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Relationship Type'),
      '#options' => $this->burndown_relationship_types(),
      '#default_value' => isset($item->type) ? $item->type : '',
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * List of options for relationship type.
   */
  public function burndown_relationship_types() {
    $options = [];

    // Get config object.
    $config = \Drupal::config('burndown.config_settings');
    $list = $config->get('relationship_types');

    if (!empty($list)) {
      // List is a text string with one item per line.
      $list = preg_split("/\r\n|\n|\r/", $list);

      foreach($list as $row) {
        // Rows are in the form "id|label".
        $val = explode('|', $row);
        $options[$val[0]] = strval($val[1]);
      }
    }

    return $options;
  }

}
