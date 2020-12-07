<?php

namespace Drupal\template_entities_book\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\template_entities\Access\EntityAccessCheck;

/**
 * Provides an access check for book pages linked to a book template.
 *
 * @internal
 */
class BookTemplatePageAccessCheck extends EntityAccessCheck {

  /**
   * Checks access to entities used as templates.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {

    // Allow if template administrator.
    if ($account->hasPermission("administer template entities")) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $requirement = $route_match->getRouteObject()->getRequirement('_entity_access');
    [$entity_type, $operation] = explode('.', $requirement);
    // If $entity_type parameter is a valid entity, call its own access check.
    $parameters = $route_match->getParameters();
    if ($parameters->has($entity_type)) {
      /** @var NodeInterface $node */
      $node = $parameters->get($entity_type);
      if ($node instanceof NodeInterface) {
        // Identify book page.
        if (!empty($node->book['bid']) && $node->book['bid'] <> $node->book['nid']) {
          $book_node = Node::load($node->book['bid']);

          // Use access of book node including dependency etc for the template
          // book pages.

          if ($book_node
            && $this->templateManager->isEntityTypeTemplateable($book_node->getEntityTypeId())
            && $this->templateManager->isTemplate($book_node->id(), $book_node->getEntityTypeId())) {
            return $this->checkTemplateSourceAccess($book_node, $account);
          }
        }
      }
    }

    return AccessResult::allowed();
  }

}
