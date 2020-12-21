<?php

namespace Drupal\burndown\Controller;

use Drupal\burndown\Entity\Project;
use Drupal\burndown\Entity\Sprint;
use Drupal\burndown\Entity\Swimlane;
use Drupal\burndown\Entity\Task;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class BoardController extends ControllerBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a BoardController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Callback for `burndown/board/{shortcode} route.
   */
  public function get_board($shortcode) {
    $data = [];

    // Sanitize input.
    $code = Html::escape($shortcode);

    // View builder for tasks.
    $viewBuilder = $this->entityTypeManager
      ->getViewBuilder('burndown_task');

    // Get board type.
    $board_type = "kanban";
    $project = Project::loadFromShortcode($code);
    if ($project !== FALSE) {
      $board_type = $project->getBoardType();
    }

    // For sprint boards, we need to ensure that we only grab tasks
    // for a sprint that is current and open.
    $sprint_id = NULL;
    if ($board_type === 'sprint') {
      $sprint = Sprint::getCurrentSprintFor($code);
      if ($sprint === FALSE) {
        // We don't want to show any tasks on a sprint board where
        // there isn't a current sprint.
        $sprint_id = 0;
      }
      else {
        $sprint_id = $sprint->id();
      }
    }

    // Get swimlanes.
    $swimlanes = Swimlane::getBoardSwimlanes($code);

    // Get tasks for each swimlane.
    foreach ($swimlanes as $swimlane) {
      $swimlane_name = $swimlane->getName();
      $swimlane_id = $swimlane->id();
      $swimlane_tasks = [];

      if (is_null($sprint_id) || ($sprint_id > 0)) {
        $tasks = Task::getTasksForSwimlane($code, $swimlane_name, $sprint_id);

        if (!empty($tasks)) {
          foreach ($tasks as $task) {
            $swimlane_tasks[] = [
              '#theme' => 'burndown_task_card',
              '#data' => $task->getData(),
            ];
          }
        }
      }

      $data[] = [
        'swimlane_name' => $swimlane_name,
        'swimlane_id' => $swimlane_id,
        'tasks' => $swimlane_tasks,
      ];
    }

    // Return data.
    return [
      '#theme' => 'burndown_board',
      '#data' => [
        'project' => $code,
        'board_type' => $board_type,
        'swimlanes' => $data,
      ],
      '#attached' => [
        'library' => [
          'burndown/drupal.burndown.board',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Callback for `burndown/api/backlog/change_swimlane` API method.
   */
  public function change_swimlane(Request $request) {
    // Get request data.
    $task_id = $request->request->get('task_id');
    $from_swimlane_id = $request->request->get('from_swimlane');
    $to_swimlane_id = $request->request->get('to_swimlane');

    // Sanitize input.
    $task_id = Html::escape($task_id);
    $from_swimlane_id = Html::escape($from_swimlane_id);
    $to_swimlane_id = Html::escape($to_swimlane_id);

    // Load entities.
    $task = Task::loadFromTicketID($task_id);
    $from_swimlane = Swimlane::load($from_swimlane_id);
    $to_swimlane = Swimlane::load($to_swimlane_id);

    // Validate that entities exist.
    if ($task === FALSE ||
      $from_swimlane === FALSE ||
      $to_swimlane === FALSE) {
      return new JsonResponse([
        'success' => 0,
        'message' => 'Entities do not exist.',
        'method' => 'POST',
      ]);
    }

    // Validate that entities are in same project.
    $task_project = $task->getProject();
    $from_swimlane_project = $from_swimlane->getProject();
    $to_swimlane_project = $to_swimlane->getProject();
    if ($task_project->id() !== $from_swimlane_project->id() ||
      $task_project->id() !== $to_swimlane_project->id()) {
      return new JsonResponse([
        'success' => 0,
        'message' => 'Entities are not in the same project.',
        'method' => 'POST',
      ]);
    }

    // Validate that task is currently in from_swimlane.
    if ($task->getSwimlane()->id() !== $from_swimlane->id()) {
      return new JsonResponse([
        'success' => 0,
        'message' => 'Task was not in the "from" swimlane.',
        'method' => 'POST',
      ]);
    }

    // Update swimlane.
    $task
      ->setSwimlane($to_swimlane)
      ->save();

    // Return JSON response.
    return new JsonResponse([
      'success' => 1,
      'method' => 'POST',
    ]);
  }

  /**
   * Callback for `burndown/api/board_reorder` API method.
   */
  public function reorder_board(Request $request) {
    // Get our new sort order.
    $sort = $request->request->get('sort');

    if (!empty($sort)) {
      foreach ($sort as $swimlane_id => $swimlane) {
        // Initialize counter.
        $counter = 0;

        if (!empty($swimlane)) {
          foreach ($swimlane as $ticket_id) {
            $task = Task::loadFromTicketID($ticket_id);
            if ($task !== FALSE) {
              $task
                ->setBoardSort($counter)
                ->save();

              $counter++;
            }
          }
        }
      }
    }

    // Return JSON response.
    return new JsonResponse([
      'success' => 1,
      'sort' => $sort,
      'method' => 'POST',
    ]);
  }

  /**
   * Callback for `burndown/api/board/send_to_backlog` API method.
   */
  public function send_to_backlog($ticket_id) {
    // Sanitize input.
    $id = Html::escape($ticket_id);

    $task = Task::loadFromTicketID($id);

    if ($task !== FALSE) {
      $project = $task->getProject();
      $shortcode = $project->getShortcode();
      $backlog = Swimlane::getBacklogFor($shortcode);
      if ($backlog !== FALSE) {
        $task
          ->setSwimlane($backlog)
          ->set('sprint', NULL)
          ->save();

        // Return JSON response.
        return new JsonResponse([
          'success' => 1,
          'method' => 'POST',
        ]);
      }
    }

    // Return "error" JSON response.
    return new JsonResponse([
      'success' => 0,
      'method' => 'POST',
    ]);
  }

}
