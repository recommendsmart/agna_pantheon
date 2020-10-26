<?php

namespace Drupal\rocketship_event_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event entities.
 *
 * @ingroup rocketship_event_core
 */
interface EventInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Build a renderable array of date components.
   *
   * @param string $date_format
   *   PHP date format.
   * @param string $time_format
   *   PHP time format.
   * @param string $class
   *   The CSS class to add.
   * @param bool $combine_duplicates
   *   Whether or not to combine duplicate dates.
   *
   * @return array
   *   A renderable array of dates.
   */
  public function buildDateComponent($date_format, $time_format, $class, $combine_duplicates = TRUE);

  /**
   * Get start date.
   *
   * @return int
   *   The timestamp of the start date.
   */
  public function getStartDate();

  /**
   * Sets the start date.
   *
   * @param int $timestamp
   *   The UNIX timestamp to set.
   *
   * @return static
   */
  public function setStartDate($timestamp);

  /**
   * Get the date the event is over.
   *
   * @return int
   *   The UNIX timestamp of the end date.
   */
  public function getEndDate();

  /**
   * Set the date the event is over.
   *
   * @param int $timestamp
   *   The UNIX timestamp to set.
   *
   * @return static
   */
  public function setEndDate($timestamp);

  /**
   * Gets the Event creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event.
   */
  public function getCreatedTime();

  /**
   * Sets the Event creation timestamp.
   *
   * @param int $timestamp
   *   The Event creation timestamp.
   *
   * @return \Drupal\rocketship_event_core\Entity\EventInterface
   *   The called Event entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event published status indicator.
   *
   * Unpublished Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event.
   *
   * @param bool $published
   *   TRUE to set this Event to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\rocketship_event_core\Entity\EventInterface
   *   The called Event entity.
   */
  public function setPublished($published);

}
