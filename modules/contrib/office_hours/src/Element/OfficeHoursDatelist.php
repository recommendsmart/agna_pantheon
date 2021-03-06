<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Datetime\Element\Datelist;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element.
 *
 * @FormElement("office_hours_datelist")
 */
class OfficeHoursDatelist extends Datelist {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $parent_info = parent::getInfo();

    $info = [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [
        [$class, 'processOfficeHours'],
      ],
      '#element_validate' => [
        [$class, 'validateOfficeHours'],
      ],

      // @see Drupal\Core\Datetime\Element\Datelist.
      '#date_part_order' => ['year', 'month', 'day', 'hour', 'minute'],
      // @see Drupal\Core\Datetime\Element\Datetime.
      '#date_date_element' => 'none', // {'none'|'date'}
      '#date_time_element' => 'time', // {'none'|'time'|'text'}
      '#date_date_format' => 'none',
      //'#date_date_callbacks' => [],
      //'#date_time_format' => 'time', // see format_date()
      '#date_time_callbacks' => [], // Can be used to add a jQuery time picker or an 'All day' checkbox.
      '#date_year_range' => '1900:2050',
      // @see Drupal\Core\Datetime\Element\DateElementBase.
      '#date_timezone' => '+0000', // new \DateTimezone(DATETIME_STORAGE_TIMEZONE),
    ];

    // #process, #validate bottom-up.
    $info['#process'] = array_merge($parent_info['#process'], $info['#process']);
    $info['#element_validate'] = array_merge($parent_info['#element_validate'], $info['#element_validate']);

    return $info + $parent_info;
  }

  /**
   * Callback for office_hours_select element.
   *
   * @param array $element
   * @param mixed $input
   * @param FormStateInterface $form_state
   * @return array|mixed|null
   *
   * Takes the #default_value and dissects it in hours, minutes and ampm indicator.
   * Mimics the date_parse() function.
   *   g = 12-hour format of an hour without leading zeros 1 through 12
   *   G = 24-hour format of an hour without leading zeros 0 through 23
   *   h = 12-hour format of an hour with leading zeros   01 through 12
   *   H = 24-hour format of an hour with leading zeros   00 through 23
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if ($input == FALSE) {
      // Prepare the numeric value: use a DateTime value.
      $time = $element['#default_value'];
      $date = NULL;
      try {
        if (is_array($time)) {
          $date = OfficeHoursDateHelper::createFromArray($time);
        }
        elseif (is_numeric($time)) {
          $timezone = $element['#date_timezone'];
          // The Date function needs a fixed format, so format $time to '0030'.
          $time = OfficeHoursDatetime::get($time, 'Hi');
          $date = OfficeHoursDateHelper::createFromFormat('Gi', $time, $timezone);
        }
      }
      catch (\Exception $e) {
        $date = NULL;
      }
      $element['#default_value'] = $date;
    }
    // Set empty minutes field to '00' for better UX.
    if (!empty($input['hour']) && isset($input['minute']) && empty($input['minute'])) {
      $input['minute'] = '00';
    }
    $input = parent::valueCallback($element, $input, $form_state);
    return $input;
  }

  /**
   * Process the office_hours_select element before showing it.
   *
   * @param $element
   * @param FormStateInterface $form_state
   * @param $complete_form
   *
   * @return
   */
  public static function processOfficeHours(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['hour']['#options'] = $element['#hour_options'];
    return $element;
  }

  /**
   * Validate the hours selector element.
   *
   * @param $element
   * @param FormStateInterface $form_state
   * @param $complete_form
   */
  public static function validateOfficeHours(&$element, FormStateInterface $form_state, &$complete_form) {
    $input = $element['#value'];

    $value = '';
    if (isset($input['object']) && $input['object']) {
      $value = (string) $input['object']->format('Gi');
      // Set the value for usage in OfficeHoursList::validateOfficeHoursSlot().
      $element['#value'] = $value;
    }
    $form_state->setValueForElement($element, $value);
  }

}
