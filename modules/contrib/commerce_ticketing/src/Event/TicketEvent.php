<?php

namespace Drupal\commerce_ticketing\Event;

use Drupal\commerce_ticketing\CommerceTicketInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the ticket event.
 *
 * @see \Drupal\commerce_ticketing\Event\TicketEvents
 */
class TicketEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_ticketing\CommerceTicketInterface
   */
  protected $ticket;

  /**
   * Constructs a new OrderEvent.
   *
   * @param \Drupal\commerce_ticketing\CommerceTicketInterface $ticket
   *   The order.
   */
  public function __construct(CommerceTicketInterface $ticket) {
    $this->ticket = $ticket;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_ticketing\CommerceTicketInterface
   *   Gets the order.
   */
  public function getTicket() {
    return $this->ticket;
  }

}
