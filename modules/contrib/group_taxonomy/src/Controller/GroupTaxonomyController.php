<?php

namespace Drupal\group_taxonomy\Controller;

use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupInterface;

/**
 * Controller for 'Taxonomy' tab/route.
 */
class GroupTaxonomyController extends GroupContentController {

  /**
   * Create a list of Taxonomies in the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The current group.
   *
   * @return mixed
   *   Renderable list of taxonomies that belongs to the group.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function groupContentOverview(GroupInterface $group) {
    $class = '\Drupal\group_taxonomy\GroupTaxonomyContentListBuilder';
    $definition = $this->entityTypeManager()->getDefinition('group_content');
    return $this->entityTypeManager()->createHandlerInstance($class, $definition)->render();
  }

  /**
   * Title for the Taxonomies list overview.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The current group.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function groupContentOverviewTitle(GroupInterface $group) {
    return $this->t("%label taxonomies", ['%label' => $group->label()]);
  }

}
