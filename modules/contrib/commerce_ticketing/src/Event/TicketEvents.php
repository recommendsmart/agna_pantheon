<?php

namespace Drupal\commerce_ticketing\Event;

final class TicketEvents {

  /**
   * Name of the event fired after loading an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_ticketing\Event\OrderEvent
   */
  const TICKET_LOAD = 'commerce_ticketing.commerce_ticket.load';

  /**
   * Name of the event fired after creating a new order.
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_ticketing\Event\OrderEvent
   */
  const TICKET_CREATE = 'commerce_ticketing.commerce_ticket.create';

  /**
   * Name of the event fired before saving an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_ticketing\Event\OrderEvent
   */
  const TICKET_PRESAVE = 'commerce_ticketing.commerce_ticket.presave';

  /**
   * Name of the event fired after saving a new order.
   *
   * @Event
   *
   * @see \Drupal\commerce_ticketing\Event\OrderEvent
   */
  const TICKET_INSERT = 'commerce_ticketing.commerce_ticket.insert';

  /**
   * Name of the event fired after saving an existing order.
   *
   * @Event
   *
   * @see \Drupal\commerce_ticketing\Event\OrderEvent
   */
  const TICKET_UPDATE = 'commerce_ticketing.commerce_ticket.update';

  /**
   * Name of the event fired before deleting an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_ticketing\Event\OrderEvent
   */
  const TICKET_PREDELETE = 'commerce_ticketing.commerce_ticket.predelete';

  /**
   * Name of the event fired after deleting an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_ticketing\Event\OrderEvent
   */
  const TICKET_DELETE = 'commerce_ticketing.commerce_ticket.delete';

}
