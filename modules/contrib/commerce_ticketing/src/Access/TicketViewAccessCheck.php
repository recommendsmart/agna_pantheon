<?php

namespace Drupal\commerce_ticketing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for the Ticket view route.
 */
class TicketViewAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TicketViewAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the Ticket view..
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_ticketing\CommerceTicketInterface $ticket */
    $ticket = $route_match->getParameter('uuid');

    if ($ticket) {
      $has_view_access = $ticket->access('view');
      $is_active = $ticket->getState()->getId() == 'active' ? AccessResult::allowed() : AccessResult::forbidden();

      return AccessResult::allowedIf($has_view_access)
        ->andIf($is_active)
        ->addCacheableDependency($ticket);
    }
    return AccessResult::neutral();
  }

}
