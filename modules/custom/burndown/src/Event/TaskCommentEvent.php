<?php

namespace Drupal\burndown\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Event that is fired when someone comments on a task.
 */
class TaskCommentEvent extends Event {

  const COMMENTED = 'burndown_event_task_comment';

  /**
   * The task.
   *
   * @var Drupal\Core\Entity\EntityInterface;
   */
  public $task;

  /**
   * The comment.
   *
   * @var string;
   */
  public $comment;

  /**
   * Constructs the object.
   *
   * @param use Drupal\Core\Entity\EntityInterface $task
   *   The newly created task.
   */
  public function __construct(EntityInterface $task, $comment) {
    $this->task = $task;
    $this->comment = $comment;
  }

}
