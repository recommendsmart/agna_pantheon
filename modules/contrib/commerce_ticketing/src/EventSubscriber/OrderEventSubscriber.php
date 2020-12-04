<?php

namespace Drupal\commerce_ticketing\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_ticketing\Entity\CommerceTicket;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Constructs a new TicketNumberSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LockBackendInterface $lock) {
    $this->entityTypeManager = $entity_type_manager;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['onPlaceTransition'],
      'commerce_order.validate.post_transition' => ['onValidateTransition'],
      'commerce_order.fulfill.post_transition' => ['onFulfillTransition'],
      'commerce_order.cancel.post_transition' => ['onCancelTransition'],
      'commerce_order.commerce_order.presave' => ['onOrderPresave', -100],
    ];
  }

  /**
   * Create the order's tickets when the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onPlaceTransition(WorkflowTransitionEvent $event) {
    // phpcs:disable
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    // phpcs:enable
  }

  /**
   * Create the order's tickets when the order is validated.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onValidateTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->autoActivateTickets($order);
  }

  /**
   * Create the order's tickets when the order is fulfilled.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onFulfillTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->autoActivateTickets($order);
  }

  /**
   * Cancels the order's tickets when the order is canceled.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onCancelTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->cancelTickets($order);
  }

  /**
   * Event listener for order presave event.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $orderEvent
   *   The order event.
   */
  public function onOrderPresave(OrderEvent $orderEvent) {
    $order = $orderEvent->getOrder();

    // Allow other modules to abort the ticket creation.
    if (!empty($order->ignore_update)) {
      return;
    }

    // Automatically create tickets for all placed orders.
    if ($order->getState()->getId() != 'draft') {
      // Only proceed, if order is paid.
      if ($order->isPaid()) {
        $this->createTickets($order);
        $this->autoActivateTickets($order);
      }
    }

  }

  /**
   * Create tickets for all relevant line items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   */
  private function createTickets(OrderInterface $order) {
    $line_items = $order->getItems();
    $existing_tickets = $order->get('tickets')->referencedEntities();
    $sorted_tickets = [];

    /** @var CommerceTicket $existing_ticket */
    foreach ($existing_tickets as $existing_ticket) {
      if (!empty($item_id = $existing_ticket->getOrderItemId())) {
        $sorted_tickets[] = $item_id;
      }
    }
    $sorted_tickets = array_count_values($sorted_tickets);

    foreach ($line_items as $line_item) {
      $purchased_entity = $line_item->getPurchasedEntity();
      if (!empty($purchased_entity)) {
        /** @var ProductVariationType $variation_type */
        $variation_type = ProductVariationType::load($purchased_entity->bundle());
        /** @var OrderType $order_type */
        $order_type = OrderType::load($order->bundle());
        $ticket_type = $order_type->getThirdPartySetting('commerce_ticketing', 'ticket_type');
        $order_state = $variation_type->getThirdPartySetting('commerce_ticketing', 'order_state');
        $auto_create_ticket = $variation_type->getThirdPartySetting('commerce_ticketing', 'auto_create_ticket');
        $auto_activate_ticket = $variation_type->getThirdPartySetting('commerce_ticketing', 'auto_activate_ticket');

        $default_state = 'created';
        if ($auto_activate_ticket && $order->getState()->getId() == $order_state) {
          $default_state = 'active';
        }

        if (!empty($ticket_type) && $auto_create_ticket) {
          // Create multiple tickets per line item.
          $quantity = $line_item->getQuantity();
          $current_quantity = !empty($sorted_tickets[$line_item->id()]) ? $sorted_tickets[$line_item->id()] : 0;

          if (empty($sorted_tickets[$line_item->id()]) || $current_quantity < $quantity) {

            for ($i = $current_quantity; $i < $quantity; $i++) {
              $ticket = CommerceTicket::create(
                [
                  'name' => $this->t('Ticket') . ' ' . $purchased_entity->label(),
                  'bundle' => $ticket_type,
                  'state' => $default_state,
                  'uid' => $order->get('uid'),
                  'order_id' => $order->id(),
                  'order_item_id' => $line_item->id(),
                ]
              );
              $ticket->save();
            }
          }
        }
      }
    }

    // Update backreference on order.
    $storage = $this->entityTypeManager->getStorage('commerce_ticket');
    $ticket_ids = $storage->getQuery()
      ->condition('order_id', $order->id())
      ->sort('id')
      ->execute();

    $order->set('tickets', $ticket_ids);

  }

  /**
   * Activate tickets.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   */
  private function autoActivateTickets(OrderInterface $order) {
    $existing_tickets = $order->get('tickets')->referencedEntities();
    /** @var \Drupal\commerce_ticketing\CommerceTicketInterface $ticket */
    foreach ($existing_tickets as $ticket) {
      if ($ticket->getPurchasedEntity()) {
        $variation_type = ProductVariationType::load($ticket->getPurchasedEntity()->bundle());
        $order_state = $variation_type->getThirdPartySetting('commerce_ticketing', 'order_state');
        $auto_activate_ticket = $variation_type->getThirdPartySetting('commerce_ticketing', 'auto_activate_ticket');
        if ($auto_activate_ticket && $order->getState()->getId() == $order_state && $ticket->getState()->getId() == 'created') {
          $ticket_state = $ticket->getState();
          $ticket_state_transitions = $ticket_state->getTransitions();
          $ticket_state->applyTransition($ticket_state_transitions['activate']);
          $ticket->save();
        }
      }
    }
  }

  /**
   * Cancel all related tickets.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function cancelTickets(OrderInterface $order) {
    // Cancel all related tickets
    $storage = $this->entityTypeManager->getStorage('commerce_ticket');
    $ticket_ids = $storage->getQuery()
      ->condition('order_id', $order->id())
      ->sort('id')
      ->execute();

    $tickets = $storage->loadMultiple($ticket_ids);
    foreach ($tickets as $ticket) {
      $ticket_state = $ticket->getState();
      $ticket_state_transitions = $ticket_state->getTransitions();
      if (!empty($ticket_state_transitions['cancel'])) {
        $ticket_state->applyTransition($ticket_state_transitions['cancel']);
        $ticket->save();
      }
    }

  }

}
