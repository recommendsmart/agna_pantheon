<?php

namespace Drupal\rocketship_event_core\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\Select;

/**
 * Provides a 'select' element.
 *
 * @WebformElement(
 *   id = "event_select",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Select.php/class/Select",
 *   label = @Translation("Event Select"),
 *   description = @Translation("Dropdown to select Events when the webform is placed on a Node page with a field_events field. Has normal Select logic otherwise."),
 *   category = @Translation("Options elements"),
 * )
 */
class EventSelect extends Select {

}
