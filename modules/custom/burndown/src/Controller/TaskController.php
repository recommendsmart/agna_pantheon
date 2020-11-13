<?php

namespace Drupal\burndown\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\burndown\Entity\TaskInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\burndown\Entity\Task;
use Drupal\burndown\Entity\Swimlane;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\burndown\Event\TaskCommentEvent;
use Drupal\burndown\Event\TaskWorkEvent;

/**
 * Class TaskController.
 *
 *  Returns responses for Task routes.
 */
class TaskController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Event dispatcher.
   *
   * @var Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $event_dispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    $instance->event_dispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * Get the log for a task (optionally filtered by type).
   *
   * @param int $ticket_id
   *   The Task ID.
   *
   * @param string $type
   *   The type of log to return.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function get_task_log($ticket_id, $type = 'all') {
    $data = [];

    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      // Task doesn't exist; throw 404.
      throw new NotFoundHttpException();
    }

    $log = $task->getLog();
    if (!empty($log)) {
      foreach ($log as $log_item) {
        if ($type !== 'all') {
          if ($log_item['type'] !== $type) {
            continue;
          }
        }
        
        // Get user name.
        $user = User::load($log_item['uid']);
        $log_item['user'] = $user->getDisplayName();

        // Date format.
        $created_date = date('r', intval($log_item['created']));
        $log_item['created'] = $created_date;

        $data[] = $log_item;
      }
    }

    $build = [
      '#theme' => 'burndown_log_items',
      '#data' => $data,
    ];

    return new Response(render($build));
  }

  /**
   * Add a comment to a Task.
   *
   * @param int $ticket_id
   *   The Task ID.
   *
   * @param string $comment
   *   The comment (rich HTML).
   */
  public function add_comment( Request $request ) {
    // Get data from request (validated below).
    $ticket_id = $request->request->get('ticket_id');
    $comment = $request->request->get('comment');

    // Load the task.
    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      // Task doesn't exist; throw 404.
      throw new NotFoundHttpException();
    }

    // Add comment to the log.
    $type = 'comment';
    $filtered_comment = Xss::filter($comment);
    $work_done = '';
    $created = time();
    $uid = \Drupal::currentUser()->id();
    $description = '';

    $task
      ->addLog($type, $filtered_comment, $work_done, $created, $uid, $description)
      ->save();

    // Instantiate our event.
    $event = new TaskCommentEvent($task, $filtered_comment);

    // Get the event_dispatcher service and dispatch the event.
    $this->event_dispatcher->dispatch(TaskCommentEvent::COMMENTED, $event);

    // Return JSON response.
    return new JsonResponse([
      'success' => 1,
      'method' => 'POST',
    ]);
  }

  /**
   * Add a work log to the Task.
   *
   * @param int $ticket_id
   *   The Task ID.
   *
   * @param string $comment
   *   A comment (rich HTML).
   *
   * @param numeric $work
   *   The quantity of work done.
   *
   * @param char $work_increment
   *   A single char describing the period (i.e. 'm' == minutes).
   */
  public static function add_work( Request $request ) {
    // Get data from request (validated below).
    $ticket_id = $request->request->get('ticket_id');
    $comment = $request->request->get('comment');
    $work = $request->request->get('work');
    $work_increment = $request->request->get('work_increment');

    // Load the task.
    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      // Task doesn't exist; throw 404.
      throw new NotFoundHttpException();
    }

    // Validate the $work is numeric.
    if (!is_numeric($work)) {
      throw new NotFoundHttpException();
    }

    // Validate that $work_increment is one of allowed list.
    $allowed = ['m', 'h', 'd', 'w', 'M', 'Y'];
    if (!in_array($work_increment, $allowed)) {
      throw new NotFoundHttpException();
    }

    // Add work to the log.
    $type = 'work';
    $filtered_comment = Xss::filter($comment);
    $work_done = $work . $work_increment;
    $created = time();
    $uid = \Drupal::currentUser()->id();
    $description = '';

    $task
      ->addLog($type, $filtered_comment, $work_done, $created, $uid, $description)
      ->save();

    // Instantiate our event.
    $event = new TaskWorkEvent($task, $filtered_comment, $work_done, $uid);

    // Get the event_dispatcher service and dispatch the event.
    $this->event_dispatcher->dispatch(TaskWorkEvent::WORKED, $event);

    // Return JSON response.
    return new JsonResponse([
      'success' => 1,
      'method' => 'POST',
    ]);
  }

  /**
   * Add a user to the task watchlist.
   *
   * @param varchar $ticket_id
   *   The Task ID.
   *
   * @param int $user_id
   *   The User ID.
   */
  public static function add_to_watchlist($ticket_id, $user_id) {
    // Load the task.
    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      // Task doesn't exist; throw 404.
      throw new NotFoundHttpException();
    }

    // Load the user.
    $user = User::load($user_id);
    if ($user === FALSE) {
      throw new NotFoundHttpException();
    }

    // Check if this is the current user.
    $current_user = \Drupal::currentUser();
    if ($current_user->id() !== $user_id) {
      // Only allow admins to subscribe somebody else.
      $current_user_roles = $current_user->getRoles();
      if (!in_array('administrator', $current_user_roles)) {
        throw new NotFoundHttpException();
      }
    }

    // Add to watchlist.
    $task->addToWatchlist($user)->save();

    // Return JSON response.
    return new JsonResponse([
      'ticket_id' => $ticket_id,
      'user_id' => $user_id,
      'success' => 1,
      'method' => 'POST',
    ]);
  }

  /**
   * Remove a user from the task watchlist.
   *
   * @param varchar $ticket_id
   *   The Task ID.
   *
   * @param int $user_id
   *   The User ID.
   */
  public static function remove_from_watchlist($ticket_id, $user_id) {
    // Load the task.
    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      // Task doesn't exist; throw 404.
      throw new NotFoundHttpException();
    }

    // Load the user.
    $user = User::load($user_id);
    if ($user === FALSE) {
      throw new NotFoundHttpException();
    }

    // Check if this is the current user.
    $current_user = \Drupal::currentUser();
    if ($current_user->id() !== $user_id) {
      // Only allow admins to unsubscribe somebody else.
      $current_user_roles = $current_user->getRoles();
      if (!in_array('administrator', $current_user_roles)) {
        throw new NotFoundHttpException();
      }
    }

    // Remove from watchlist.
    $task->removeFromWatchlist($user);

    // Return JSON response.
    return new JsonResponse([
      'ticket_id' => $ticket_id,
      'user_id' => $user_id,
      'success' => 1,
      'method' => 'POST',
    ]);
  }

  /**
   * Get list of relationships for a ticket for AJAX endpoint.
   *
   * @param varchar $ticket_id
   *   The Task ID.
   */
  public function get_relationships($ticket_id) {
    $data = [];

    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      return new JsonResponse([
        'message' => 'Task does not exist',
        'success' => 0,
        'method' => 'POST',
      ]);
    }
    $task_id = $task->id();
    $task_is_completed = $task->isCompleted();

    // Get relationships for the task.
    $relationships = $task->getRelationships();

    foreach ($relationships as $relationship) {
      $to_task = Task::load($relationship['task_id']);

      $data[] = [
        'local' => 1,
        'from_task_id' => $task_id,
        'from_ticket_id' => $ticket_id,
        'from_task_completed' => $task_is_completed,
        'to_task_id' => $relationship['task_id'],
        'to_ticket_id' => $to_task->getTicketID(),
        'to_task_completed' => $to_task->isCompleted(),
        'type' => $relationship['type'],
      ];
    }

    // Get back reference relationships.
    $back_relationships = $task->getRelationshipReferences();
    if (!empty($back_relationships)) {
      foreach ($back_relationships as $relationship) {
        $to_task = Task::load($relationship['task_id']);

        $data[] = [
          'local' => 0,
          'from_task_id' => $task_id,
          'from_ticket_id' => $ticket_id,
          'from_task_completed' => $task_is_completed,
          'to_task_id' => $relationship['task_id'],
          'to_task_completed' => $relationship['is_completed'],
          'to_ticket_id' => $to_task->getTicketID(),
          'type' => $relationship['type'],
        ];
      }
    }

    // Render partial.
    $build = [
      '#theme' => 'burndown_task_relationships',
      '#data' => $data,
    ];

    return new Response(render($build));

  }

  /**
   * Add a relationship between tasks.
   */
  public function add_relationship( Request $request ) {
    // Get data from request (validated below).
    $from_ticket_id = $request->request->get('from_ticket_id');
    $to_ticket_id = $request->request->get('to_ticket_id');
    $type = $request->request->get('type');

    if ($from_ticket_id == $to_ticket_id) {
      return new JsonResponse([
        'message' => 'Task cannot be related to itself',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    // Load the tasks:

    $from_ticket_id = Xss::filter($from_ticket_id);
    $from_task = Task::loadFromTicketID($from_ticket_id);
    if ($from_task === FALSE) {
      return new JsonResponse([
        'from_ticket_id' => $from_ticket_id,
        'message' => 'Task does not exist',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    $to_ticket_id = Xss::filter($to_ticket_id);
    $to_task = Task::loadFromTicketID($to_ticket_id);
    if ($to_task === FALSE) {
      return new JsonResponse([
        'to_ticket_id' => $to_ticket_id,
        'message' => 'Task does not exist',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    $from_task_id = $from_task->id();
    $to_task_id = $to_task->id();

    // Filter $type.
    $filtered_type = Xss::filter($type);

    // Check that $type is valid.
    if (Task::relationshipTypeExists($filtered_type) === FALSE) {
      return new JsonResponse([
        'message' => 'Invalid relationship type',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    // Check if there is a relationship between the two tasks.
    // We have to test both tasks, as it could reside on either.
    if ($from_task->checkIfRelationshipExists($to_task_id) !== FALSE ||
      $to_task->checkIfRelationshipExists($from_task_id) !== FALSE) {
      return new JsonResponse([
        'message' => 'There is already a relationship between these tickets',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    // Everything is okay; add the relationship.
    $from_task
      ->addRelationship($to_task_id, $filtered_type)
      ->save();

    // Return "ok".
    return new JsonResponse([
      'success' => 1,
      'method' => 'POST',
    ]);
  }

  /**
   * Remove a relationship between two tasks.
   *
   * @param varchar $from_ticket_id
   *   The Task ID where the relationship is stored.
   *
   * @param varchar $to_ticket_id
   *   The Task ID that the relationship relates to.
   */
  public function remove_relationship($from_ticket_id, $to_ticket_id) {
    // Load the tasks:

    $from_task = Task::loadFromTicketID($from_ticket_id);
    if ($from_task === FALSE) {
      return new JsonResponse([
        'message' => 'Task does not exist',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    $to_task = Task::loadFromTicketID($to_ticket_id);
    if ($to_task === FALSE) {
      return new JsonResponse([
        'message' => 'Task does not exist',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    $from_task_id = $from_task->id();
    $to_task_id = $to_task->id();

    // We need to check both from/to tasks, as the relationship could
    // reside on either:
    if ($from_task->checkIfRelationshipExists($to_task_id) !== FALSE) {
      $from_task->removeRelationship($to_task_id);
    }
    else if ($to_task->checkIfRelationshipExists($from_task_id) !== FALSE) {
      $to_task->removeRelationship($from_task_id);
    }
    else {
      // Relationship doesn't exist.
      return new JsonResponse([
        'from_task_id' => $from_task_id,
        'to_task_id' => $to_task_id,
        'message' => 'Relationship does not exist',
        'success' => 0,
        'method' => 'POST',
      ]);
    }

    // Success!
    return new JsonResponse([
      'success' => 1,
      'method' => 'POST',
    ]);
  }

  /**
   * Reopens a closed task.
   *
   * @param int $ticket_id
   *   The Task ID.
   */
  public function reopen_task($ticket_id) {
    // Load task.
    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      // Task doesn't exist; warn and redirect.
      \Drupal::messenger()->addStatus("Task does not exist.");
      $url = Url::fromUri('internal:/burndown');
      $response = new RedirectResponse($url->toString());
      $response->send();
    }

    // Check if task is actually closed.
    if ($task->getCompleted() == FALSE) {
      \Drupal::messenger()->addStatus("Task is not closed.");
      $url = Url::fromUri('internal:/burndown/task/' . $task->id() . '/edit');
      $response = new RedirectResponse($url->toString());
      $response->send();
    }

    // Get project.
    $project = $task->getProject();
    $shortcode = $project->getShortcode();

    // Get backlog.
    $backlog = Swimlane::getBacklogFor($shortcode);

    // Set task to open.
    $task
        ->setCompleted(FALSE)
        ->setSwimlane($backlog)
        ->setResolution('')
        ->save();

    // Redirect to backlog.
    \Drupal::messenger()->addStatus("Task has been reopened.");
    $url = Url::fromUri('internal:/burndown/backlog/' . $shortcode);
    $response = new RedirectResponse($url->toString());
    $response->send();
  }

  /**
   * Callback for task bundle add route.  
   */
  public function addBundleSelect($shortcode) {
    // Get list of bundles.
    $bundles = \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo('burndown_task');

    $destination = '/burndown/backlog/' . $shortcode;

    $links = [];

    // Create links to add pages.
    foreach ($bundles as $bundle_id => $bundle) {
      $add_link = '/burndown/task/add/' . $bundle_id;
      $task_type = $bundle['label'];
      $link_text = t('Add a @type Task', ['@type' => $task_type]);
      $links[] = Link::fromTextAndUrl($link_text, Url::fromUri('base:' . $add_link, [
        'absolute' => TRUE,
        'query' => [
          'shortcode' => $shortcode,
          'destination' => $destination,
        ],
      ]));
    }

    // Return markup.
    return [
      '#theme' => 'burndown_multi_bundle_add',
      '#links' => $links,
    ];
  }

  /**
   * Displays a Task revision.
   *
   * @param int $burndown_task_revision
   *   The Task revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($burndown_task_revision) {
    $burndown_task = $this->entityTypeManager()->getStorage('burndown_task')
      ->loadRevision($burndown_task_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('burndown_task');

    return $view_builder->view($burndown_task);
  }

  /**
   * Page title callback for a Task revision.
   *
   * @param int $burndown_task_revision
   *   The Task revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($burndown_task_revision) {
    $burndown_task = $this->entityTypeManager()->getStorage('burndown_task')
      ->loadRevision($burndown_task_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $burndown_task->label(),
      '%date' => $this->dateFormatter->format($burndown_task->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Task.
   *
   * @param \Drupal\burndown\Entity\TaskInterface $burndown_task
   *   A Task object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TaskInterface $burndown_task) {
    $account = $this->currentUser();
    $burndown_task_storage = $this->entityTypeManager()->getStorage('burndown_task');

    $langcode = $burndown_task->language()->getId();
    $langname = $burndown_task->language()->getName();
    $languages = $burndown_task->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $burndown_task->label()]) : $this->t('Revisions for %title', ['%title' => $burndown_task->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all task revisions") || $account->hasPermission('administer task entities')));
    $delete_permission = (($account->hasPermission("delete all task revisions") || $account->hasPermission('administer task entities')));

    $rows = [];

    $vids = $burndown_task_storage->revisionIds($burndown_task);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\burndown\TaskInterface $revision */
      $revision = $burndown_task_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $burndown_task->getRevisionId()) {
          $link = $this->l($date, new Url('entity.burndown_task.revision', [
            'burndown_task' => $burndown_task->id(),
            'burndown_task_revision' => $vid,
          ]));
        }
        else {
          $link = $burndown_task->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.burndown_task.translation_revert', [
                'burndown_task' => $burndown_task->id(),
                'burndown_task_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.burndown_task.revision_revert', [
                'burndown_task' => $burndown_task->id(),
                'burndown_task_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.burndown_task.revision_delete', [
                'burndown_task' => $burndown_task->id(),
                'burndown_task_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['burndown_task_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
