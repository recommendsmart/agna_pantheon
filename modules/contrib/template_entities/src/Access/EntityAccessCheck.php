<?php

namespace Drupal\template_entities\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\template_entities\TemplateManagerInterface;
use Drupal\template_entities\TemplatePermissions;

/**
 * Provides an access check entities used as templates.
 *
 * @internal
 */
class EntityAccessCheck implements AccessInterface {

  /**
   * The template manager.
   *
   * @var \Drupal\template_entities\TemplateManagerInterface
   */
  protected TemplateManagerInterface $templateManager;

  /**
   * EntityAccessCheck constructor.
   *
   * @param \Drupal\template_entities\TemplateManagerInterface $template_manager
   */
  public function __construct(TemplateManagerInterface $template_manager) {
    $this->templateManager = $template_manager;
  }

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
      /** @var EntityInterface $entity */
      $entity = $parameters->get($entity_type);
      if ($entity instanceof EntityInterface
          && $this->templateManager->isEntityTypeTemplateable($entity->getEntityTypeId())
          && $this->templateManager->isTemplate($entity->id(), $entity->getEntityTypeId())) {

        return $this->checkTemplateSourceAccess($entity, $account);
      }
    }

    return AccessResult::allowed();
  }

  /**
   * Check entity used as template against permissions for each template it's
   * referenced by.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  protected function checkTemplateSourceAccess(EntityInterface $entity, $account) {
    $result = AccessResult::allowed();

    // For each template that uses this entity (normally only one),
    // then determine who gets access.
    foreach ($this->templateManager->getTemplatesForEntity($entity) as $template) {
      if (!($account->hasPermission(TemplatePermissions::manageTemplatesId($template->bundle()))
        || $account->hasPermission(TemplatePermissions::newFromTemplateId($template->bundle())))) {
        $result = $result->andIf(AccessResult::forbidden()->cachePerPermissions()->addCacheableDependency($template));
      }
      else {
        $result = $result->andIf(AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($template));
      }
    }

    return $result;
  }
}
