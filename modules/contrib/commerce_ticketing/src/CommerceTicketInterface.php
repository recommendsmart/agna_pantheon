<?php

namespace Drupal\commerce_ticketing;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a commerce ticket entity type.
 */
interface CommerceTicketInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the ticket number.
   *
   * @return string
   *   Number of the ticket.
   */
  public function getTicketNumber();

  /**
   * Sets the ticket title.
   *
   * @param string $ticket_number
   *   The ticket ticket number.
   *
   * @return \Drupal\commerce_ticketing\CommerceTicketInterface
   *   The called ticket entity.
   */
  public function setTicketNumber($ticket_number);

  /**
   * Gets the ticket state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The ticket state.
   */
  public function getState();

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   Order of the ticket.
   */
  public function getOrder();

  /**
   * Gets the order id.
   *
   * @return string
   *   Order id of the ticket.
   */
  public function getOrderId();

  /**
   * Checks it the ticket has an order.
   */
  public function hasOrder();

  /**
   * Gets the order item id.
   *
   * @return string
   *   Order item of the ticket.
   */
  public function getOrderItemId();

  /**
   * Gets the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   Order item of the ticket.
   */
  public function getOrderItem();

  /**
   * Gets the commerce ticket creation timestamp.
   *
   * @return int
   *   Creation timestamp of the commerce ticket.
   */
  public function getCreatedTime();

  /**
   * Sets the commerce ticket creation timestamp.
   *
   * @param int $timestamp
   *   The commerce ticket creation timestamp.
   *
   * @return \Drupal\commerce_ticketing\CommerceTicketInterface
   *   The called commerce ticket entity.
   */
  public function setCreatedTime($timestamp);

}
