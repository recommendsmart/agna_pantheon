<?php

namespace Drupal\commerce_ticketing;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Ticket entities.
 */
class CommerceTicketViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Add state dropwdown filter.
    $data['commerce_ticket']['state']['filter']['id'] = 'state_machine_state';
    return $data;
  }

}
