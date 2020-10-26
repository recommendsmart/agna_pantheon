<?php

namespace Drupal\rocketship_service_basic\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MigrateEventSubscriber.
 */
class MigrateEventSubscriber implements EventSubscriberInterface {

  public static $migrateId = 'rocketship_service_overview';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE] = ['onMigratePostRowSaveEvent'];

    return $events;
  }

  /**
   * Callback for the event.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onMigratePostRowSaveEvent(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration();
    if ($migration->id() === static::$migrateId) {
      $nid = reset($event->getDestinationIdValues());
      $node = Node::load($nid);
      $uuid = $node->uuid();

      switch ($uuid) {
        case 'ab3aa739-180e-4432-9a19-1ea36a915d0b':
          // Set the canonical URL to the current page + pager.
          $metatags = $node->get('field_meta_tags')->value;
          $metatags = unserialize($metatags);

          $metatags['canonical_url'] = '[current-page:paged-url]';

          $serialized = serialize($metatags);
          $node->set('field_meta_tags', $serialized);
          $node->save();
          break;
      }
    }
  }

}
