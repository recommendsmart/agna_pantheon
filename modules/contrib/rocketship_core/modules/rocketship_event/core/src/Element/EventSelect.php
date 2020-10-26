<?php

namespace Drupal\rocketship_event_core\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * EventSelect class.
 *
 * Provides a form element which, when placed on the same page as a Node with
 * field_events field, will populate the options list with dates from the
 * Events.
 *
 * @FormElement("event_select")
 */
class EventSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $node = \Drupal::request()->attributes->get('node');
    /** @var \Drupal\node\NodeInterface $node */
    if ($node && !$node instanceof NodeInterface) {
      $node = Node::load($node);
    }
    if ($node instanceof NodeInterface && $node->hasField('field_events')) {
      static::setEventOptions($element, $form_state, $complete_form, $node);
    }

    $element = parent::processSelect($element, $form_state, $complete_form);

    // Must convert this element['#type'] to a 'select' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'select';

    // Add class.
    $element['#attributes']['class'][] = 'event-entity-select';

    return $element;
  }

  /**
   * Set the event options.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   * @param \Drupal\node\NodeInterface $node
   *   The Node.
   */
  public static function setEventOptions(array &$element,
                                         FormStateInterface $form_state,
                                         array &$complete_form,
                                         NodeInterface $node) {
    $complete_form['#cache']['tags'][] = 'node:' . $node->id();
    $complete_form['#cache']['contexts'][] = 'url';

    $options = [];
    foreach ($node->get('field_events') as $item) {
      /** @var \Drupal\rocketship_event_core\Entity\EventInterface $event */
      $event = $item->entity;

      $date = \Drupal::service('date.formatter')->format($event->getStartDate(), 'custom', 'd/m/Y');
      if (date('d/m/Y', $event->getStartDate()) === date('d/m/Y', $event->getEndDate())) {
        $date .= ' - ' . \Drupal::service('date.formatter')->format($event->getEndDate(), 'custom', 'd/m/Y');
      }
      $options[$date] = $date;
    }

    $element['#options'] = $options;

    // If there's only one option, set that as default.
    // @todo check for multiple true
    // also figure out why the fuck this doesn't work.
    if (count($element['#options']) == 1) {
      $element['#default_value'] = array_keys($element['#options']);
    }
  }

}
