<?php

namespace Drupal\arch_stock\Entity\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the warehouse type.
 */
class WarehouseData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // @todo
    return $data;
  }

}
