<?php

namespace Drupal\template_entities\Access;

use Drupal;
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
class LinkedTemplatesAccessCheck implements AccessInterface {

  /**
   * Checks routing access to the linked templates.
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
    /** @var \Drupal\template_entities\TemplateManagerInterface $template_manager */
    $template_manager = Drupal::service('template_entities.manager');

    $parameter_name = $route_match->getParameter('entity_type_id');

    /** @var EntityInterface $entity */
    $entity = $route_match->getParameter($parameter_name);

    if (!$template_manager->isTemplate($entity->id(), $entity->getEntityTypeId())) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    if (!$entity instanceof EntityInterface) {
      $access = AccessResult::forbidden();
    }
    else {
      $access = AccessResult::allowed();
    }

    return $access->addCacheableDependency($entity);
  }

}
