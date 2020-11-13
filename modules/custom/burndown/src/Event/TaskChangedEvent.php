<?php

namespace Drupal\burndown\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Event that is fired when a task is edited.
 */
class TaskChangedEvent extends Event {

  const CHANGED = 'burndown_event_task_changed';

  /**
   * The task.
   *
   * @var Drupal\Core\Entity\EntityInterface;
   */
  public $task;

  /**
   * Constructs the object.
   *
   * @param Drupal\Core\Entity\EntityInterface $task
   *   The modified task.
   */
  public function __construct(EntityInterface $task) {
    $this->task = $task;
  }

}
