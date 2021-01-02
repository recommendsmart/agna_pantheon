<?php

namespace Drupal\openfarm_holding\Service;

/**
 * OpenfarmHoldingServiceInterface file.
 */
interface OpenfarmHoldingServiceInterface {

  /**
   * Processing for opening scheduled nodes.
   */
  public function openHoldings();

  /**
   * Processing for closing scheduled nodes.
   */
  public function closeHoldings();

  /**
   * Get the number of records which belongs to specific holding.
   *
   * @param string $holding_id
   *   Holding id.
   *
   * @return string
   *   The count of records.
   */
  public function getCountOfRecords($holding_id);

}
