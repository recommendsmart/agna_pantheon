<?php

namespace Drupal\openfarm_record\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageRedirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects to another page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "duplicate_record_page_redirect",
 *   label = @Translation("Duplicate record page redirect")
 * )
 */
class DuplicateRecordPageRedirect extends PageRedirect {

  /**
   * {@inheritdoc}
   */
  public function getActionTarget(EntityInterface $entity) {
    if ($entity->bundle() !== 'record' || $entity->get('field_duplicate_of')->isEmpty()) {
      return FALSE;
    }
    return $entity->get('field_duplicate_of')->entity->toUrl()->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity, Response $current_response = NULL) {
    $target = $this->getActionTarget($entity);
    if (!$target) {
      return;
    }
    return parent::performAction($entity, $current_response);
  }

}
