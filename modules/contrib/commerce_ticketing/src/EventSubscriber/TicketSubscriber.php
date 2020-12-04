<?php

namespace Drupal\commerce_ticketing\EventSubscriber;

use Drupal\commerce_ticketing\Event\TicketEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates the ticket number for new tickets.
 *
 * Modules wishing to provide their own ticket number logic should register
 * an event subscriber with a higher priority (for example, 0).
 *
 * Modules that need access to the generated ticket number should register
 * an event subscriber with a lower priority (for example, -50).
 */
class TicketSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TicketNumberSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_ticketing.commerce_ticket.delete' => ['removeFromOrder', -30],
    ];
    return $events;
  }

  /**
   * Removes the back reference on the order.
   *
   * @param \Drupal\commerce_ticketing\Event\TicketEvent $event
   *   The event.
   *
   */
  public function removeFromOrder(TicketEvent $event) {
    /** @var \Drupal\commerce_ticketing\CommerceTicketInterface $ticket */
    $ticket = $event->getTicket();
    $order = $ticket->getOrder();
    $existing_tickets = $order->get('tickets')->getValue();
    foreach ($existing_tickets as $key => $existing_ticket) {
      if ($existing_ticket['target_id'] == $ticket->id()) {
        unset($order->get('tickets')->getValue()[$key]);
      }
    }

    $order->save();
  }

}
