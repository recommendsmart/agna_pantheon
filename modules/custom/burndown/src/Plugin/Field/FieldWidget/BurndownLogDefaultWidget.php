<?php

namespace Drupal\burndown\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field widget "burndown_log_default".
 *
 * @FieldWidget(
 *   id = "burndown_log_default",
 *   label = @Translation("Burndown Log default"),
 *   field_types = {
 *     "burndown_log",
 *   }
 * )
 */
class BurndownLogDefaultWidget extends WidgetBase implements WidgetInterface {

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

    // Default widget is for comments. We need a custom form for time logging,
    // and the other types are automatically generated.
    $element['type'] = [
      '#type' => 'hidden',
      '#default_value' => 'comment',
    ];

    // Date created.
    $element['created'] = [
      '#type' => 'hidden',
      '#default_value' => time(),
    ];

    // Author.
    $element['uid'] = [
      '#type' => 'hidden',
      '#default_value' => \Drupal::currentUser()->id(),
    ];

    $element['comment'] = [
      '#title' => t('Comment'),
      '#type' => 'text_format',
      '#format' => isset($item->comment['format']) ? $item->comment['format'] : 'basic_html',
      '#default_value' => isset($item->comment['value']) ? $item->comment['value'] : '',
    ];

    return $element;
  }

}
