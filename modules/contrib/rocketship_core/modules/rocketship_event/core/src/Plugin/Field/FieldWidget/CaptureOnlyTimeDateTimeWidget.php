<?php

namespace Drupal\rocketship_event_core\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'capture_only_time_timestamp' widget.
 *
 * @FieldWidget(
 *   id = "capture_only_time_timestamp",
 *   label = @Translation("Time only"),
 *   field_types = {
 *     "timestamp"
 *   }
 * )
 */
class CaptureOnlyTimeDateTimeWidget extends TimestampDatetimeWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'other_field_for_date' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['other_field_for_date'] = [
      '#type' => 'textfield',
      '#title' => t('Use another field to set the Date.'),
      '#description' => t("Use the Date value (not time) from another datetime field in the same form to set this field's date. If left empty, the date will be set to today."),
      '#default_value' => $this->getSetting('other_field_for_date'),
      '#weight' => -1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $use_field = $this->getSetting('other_field_for_date');
    if ($use_field) {
      $summary[] = t('Using other field for Date selection: @field', ['@field' => $use_field]);
    }
    else {
      $summary[] = t('Date will be set to today');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value']['#date_date_element'] = 'none';

    return $element;
  }

  /**
   * Get a date object based on a field item.
   *
   * @param array $item
   *   The item.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The DrupalDateTime object for the item.
   */
  protected function getDateFromItem(array $item) {
    // @todo The structure is different whether access is denied or not, to
    //   be fixed in https://www.drupal.org/node/2326533.
    if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
      $date = $item['value'];
    }
    elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
      $date = $item['value']['object'];
    }
    elseif (is_string($item['value'])) {
      $date = DrupalDateTime::createFromTimestamp($item['value']);
    }
    else {
      $date = new DrupalDateTime();
    }
    return $date;
  }

  /**
   * Get the date selected in another field.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $index
   *   The index for the other field.
   *
   * @return bool|\Drupal\Core\Datetime\DrupalDateTime
   *   A drupal datetime object.
   */
  protected function getOtherDate(array $form, FormStateInterface $form_state, $index) {
    if ($field = $this->getSetting('other_field_for_date')) {
      $form_state_values = $form_state->getValues();

      if ($form['#type'] === 'inline_entity_form') {
        // Oh boy. Inline entity form. Bleh.
        // Use the form's #parents to dig into the $form_state's values
        // so we reach the same depth as we would if this was a normal form.
        foreach ($form['#parents'] as $key) {
          $form_state_values = $form_state_values[$key];
        }
      }

      // Fetch the corresponding item (same index) from the other field.
      $other_field_values = isset($form_state_values[$field]) ? $form_state_values[$field] : [];
      if (empty($other_field_values) || !isset($other_field_values[$index]['value'])) {
        // Nothing to do here.
        return FALSE;
      }

      // We are a go. Now, grab the other value.
      return $this->getDateFromItem($other_field_values[$index]);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $index => &$item) {
      $date = $this->getDateFromItem($item);
      // Start off by setting it to today.
      $date->setDate(date('Y'), date('m'), date('d'));

      if ($field = $this->getSetting('other_field_for_date')) {
        if ($other_date = $this->getOtherDate($form, $form_state, $index)) {
          // We don't mess with timezones here. Madness lies that way.
          $date->setDate($other_date->format('Y'), $other_date->format('m'), $other_date->format('d'));
          $item['value'] = $date->getTimestamp();
          continue;
        }
      }

      $item['value'] = $date->getTimestamp();
    }
    return $values;
  }

}
