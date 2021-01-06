<?php

namespace Drupal\openfarm_statistics;

use Drupal\openfarm_statistics\Form\OpenfarmStatisticsDateSelectForm as Filter;

/**
 * Provide help method to indicate filter for charts family blocks.
 *
 * @see openfarm_charts
 *
 * @package Drupal\openfarm_statistics
 */
trait OpenfarmStatisticsFilterTrait {

  /**
   * Get filters.
   *
   * @return array|false
   *   Filters to apply.
   */
  public function getFilters() {
    // @Todo: get request service in block and pass here?
    $query = \Drupal::request()->query;
    if ($query->has(Filter::FIXED_RANGE)) {
      $range = $query->get(Filter::FIXED_RANGE);
      return [Filter::FROM => strtotime('-' . $range . ' month')];
    }
    elseif ($query->has(Filter::FROM) && $query->has(Filter::TO)) {
      return [
        Filter::FROM => strtotime($query->get(Filter::FROM)),
        Filter::TO => strtotime($query->get(Filter::TO)),
      ];
    }

    return FALSE;
  }

}
