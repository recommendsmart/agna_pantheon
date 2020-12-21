<?php

namespace Drupal\burndown;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Swimlane entities.
 *
 * @ingroup burndown
 */
class SwimlaneListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Swimlane ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\burndown\Entity\Swimlane $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.burndown_swimlane.edit_form',
      ['burndown_swimlane' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
