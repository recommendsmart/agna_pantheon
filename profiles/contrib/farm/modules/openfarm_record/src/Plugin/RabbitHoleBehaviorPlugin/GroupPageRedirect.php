<?php

namespace Drupal\openfarm_record\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageRedirect;

/**
 * Redirects to another page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "openfarm_group_page_redirect",
 *   label = @Translation("Group page redirect")
 * )
 */
class GroupPageRedirect extends PageRedirect {

  /**
   * {@inheritdoc}
   */
  public function getActionTarget(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\EntityInterface $group_content */
    foreach ($entity->getContentEntities() as $group_content) {
      if ($group_content->getEntityTypeId() === 'node' && $group_content->bundle() === 'record') {
        return $group_content->toUrl();
      }
    }

    return parent::getActionTarget($entity);
  }

}
