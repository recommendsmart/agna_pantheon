<?php

namespace Drupal\burndown\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Event that is fired when someone adds a worklog to a task.
 */
class TaskWorkEvent extends Event {

  const WORKED = 'burndown_event_task_work';

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
   * The amount of work done.
   *
   * @var string;
   */
  public $work_done;

  /**
   * The user who did the work.
   *
   * @var integer;
   */
  public $uid;

  /**
   * Constructs the object.
   *
   * @param use Drupal\Core\Entity\EntityInterface $task
   *   The newly created task.
   */
  public function __construct(EntityInterface $task, $comment, $work_done, $uid) {
    // $created, $uid,
    $this->task = $task;
    $this->comment = $comment;
    $this->work_done = $work_done;
    $this->uid = $uid;
  }

}
