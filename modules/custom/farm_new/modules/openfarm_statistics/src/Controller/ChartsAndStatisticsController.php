<?php

namespace Drupal\openfarm_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\openfarm_statistics\Form\OpenfarmStatisticsDateSelectForm;

/**
 * Class ChartsAndStatisticsController.
 */
class ChartsAndStatisticsController extends ControllerBase {

  /**
   * Charts.
   */
  public function charts() {
    return $this->formBuilder()->getForm(OpenfarmStatisticsDateSelectForm::class);
  }

}
