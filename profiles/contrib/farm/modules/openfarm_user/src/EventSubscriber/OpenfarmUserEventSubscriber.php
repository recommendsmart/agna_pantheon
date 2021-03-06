<?php

namespace Drupal\openfarm_user\EventSubscriber;

use Drupal\ckeditor_mentions\CKEditorMentionEvent;
use Drupal\comment\Entity\Comment;
use Drupal\content_moderation\Event\ContentModerationEvents;
use Drupal\content_moderation\Event\ContentModerationStateChangedEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openfarm_user\Event\OpenfarmContentModerationEvent;
use Drupal\openfarm_user\Event\OpenfarmUserEvents;
use Drupal\openfarm_user\Event\OpenfarmUserMentionEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OpenfarmUserEventSubscriber.
 */
class OpenfarmUserEventSubscriber implements EventSubscriberInterface {

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OpenfarmSocialAuthSubscriber constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager) {
    $this->eventDispatcher = $event_dispatcher;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CKEditorMentionEvent::MENTION_FIRST => 'usersAreMentioned',
      ContentModerationEvents::STATE_CHANGED => 'stateChanged',
    ];
  }

  /**
   * This method is called when the MENTION_FIRST event is dispatched.
   *
   * @param \Drupal\ckeditor_mentions\CKEditorMentionEvent $event
   *   The dispatched event.
   */
  public function usersAreMentioned(CKEditorMentionEvent $event) {
    if ((($comment = $event->getEntity()) instanceof Comment) && !empty($event->getMentionedUsers())) {
      // If user was mentioned twice in comment remove it.
      $user_ids = array_unique(array_keys($event->getMentionedUsers()));
      foreach ($user_ids as $id) {
        $storage = $this->entityTypeManager->getStorage('user');
        $user = $storage->load($id);
        $event = new OpenfarmUserMentionEvent($comment, $user);
        $this->eventDispatcher->dispatch(OpenfarmUserEvents::OPENIDEAL_USER_MENTION, $event);
      }
    }
  }

  /**
   * This method is called when the MENTION_FIRST event is dispatched.
   *
   * @param \Drupal\content_moderation\Event\ContentModerationStateChangedEvent $event
   *   The dispatched event.
   */
  public function stateChanged(ContentModerationStateChangedEvent $event) {
    $event = new OpenfarmContentModerationEvent($event);
    $this->eventDispatcher->dispatch(OpenfarmUserEvents::WORKFLOW_STATE_CHANGED, $event);
  }

}
