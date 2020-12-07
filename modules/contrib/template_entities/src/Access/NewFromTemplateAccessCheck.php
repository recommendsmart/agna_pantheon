<?php

namespace Drupal\template_entities\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access check for linked templates.
 *
 * @internal
 */
class NewFromTemplateAccessCheck implements AccessInterface {

  /**
   * Checks routing access from create new from template routes.
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
    /** @var EntityInterface $entity */
    if ($entity = $route_match->getParameter('template')) {
      $type_id = $entity->bundle();
    }
    elseif ($entity = $route_match->getParameter('template_type')) {
      $type_id = $entity->id();
    }
    elseif ($template_type_id = $route_match->getParameter('template_type_id')) {
      $type_id = $template_type_id;
    }
    else {
      $type_id = FALSE;
    }

    if (!$type_id) {
      $access = AccessResult::forbidden();
    }
    else {
      $access = AccessResult::allowedIfHasPermissions($account, [
        "manage $type_id template",
        "new from $type_id template",
      ], 'OR');
      /** @var \Drupal\template_entities\Entity\TemplateTypeInterface $template_type */
      if ($template_type = $route_match->getParameter('template_type')) {
        $template_type->getTargetEntityTypeId();
        if ($bundle = $route_match->getRawParameters()
          ->getIterator()
          ->current()) {
          $access = $access->orIf(AccessResult::forbiddenIf(!in_array($bundle, $template_type->getBundles())));
        }
      }
    }

    return $access->addCacheableDependency($entity);
  }

}
