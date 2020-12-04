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
class TicketNumberSubscriber implements EventSubscriberInterface {

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
      'commerce_ticketing.commerce_ticket.presave' => ['setTicketNumber', -30],
    ];
    return $events;
  }

  /**
   * Sets the ticket number.
   *
   * The number is generated using the number pattern specified by the
   * ticket type. If no number pattern was specified, the ticket ID is
   * used as a fallback.
   *
   * Skipped if the ticket number has already been set.
   *
   * @param \Drupal\commerce_ticketing\Event\TicketEvent $event
   *   The event.
   *
   */
  public function setTicketNumber(TicketEvent $event) {
    /** @var \Drupal\commerce_ticketing\CommerceTicketInterface $ticket */
    $ticket = $event->getTicket();
    if (!$ticket->getTicketNumber()) {
      $ticket_type_storage = $this->entityTypeManager->getStorage('commerce_ticket_type');
      /** @var \Drupal\commerce_ticketing\Entity\CommerceTicketType $ticket_type */
      $ticket_type = $ticket_type_storage->load($ticket->bundle());
      /** @var \Drupal\commerce_number_pattern\Entity\NumberPatternInterface $number_pattern */
      $number_pattern = $ticket_type->getNumberPattern();
      if ($number_pattern) {
        $ticket_number = $number_pattern->getPlugin()->generate($ticket);
      }
      else {
        $ticket_number = $ticket->id();
      }

      $ticket->setTicketNumber($ticket_number);
    }
  }

}
