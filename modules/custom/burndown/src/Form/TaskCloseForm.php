<?php

namespace Drupal\burndown\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\burndown\Entity\Task;
use Drupal\burndown\Entity\Swimlane;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\burndown\Event\TaskCreatedEvent;
use Drupal\burndown\Event\TaskClosedEvent;

/**
 * TaskCloseForm controller.
 */
class TaskCloseForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'burndown_task_close_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ticket_id = NULL, $board = 'board') {

    // Load the task.
    $task = Task::loadFromTicketID($ticket_id);
    if ($task === FALSE) {
      // Task doesn't exist; throw 404.
      throw new NotFoundHttpException();
    }

    // Prefix/suffix to allow modal reloading.
    $form['#prefix'] = '<div id="close_task_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('<h2>Close Task @ticket_id ?</h2>', [
        '@ticket_id' => $ticket_id,
      ]),
    ];

    $form['ticket_id'] = [
      '#type' => 'hidden',
      '#value' => $ticket_id,
    ];

    $form['board'] = [
      '#type' => 'hidden',
      '#value' => $board,
    ];

    $form['resolution_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Resolution Status'),
      '#options' => Task::getResolutionStatuses(),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Close task'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [
          $this,
          'submitModalFormAjax',
        ],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**   
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#close_task_form', $form));
    }
    else {
      // Retrieve task id.
      $ticket_id = $form_state->getValue('ticket_id');

      // Board to redirect back to.
      $board = $form_state->getValue('board');

      // Retrieve resolution status.
      $resolution_status = $form_state->getValue('resolution_status');

      // Get project info (for redirection) from task.
      $task = Task::loadFromTicketID($ticket_id);
      $project = $task->getProject();
      $shortcode = $project->getShortcode();
      $result = '/burndown/' . $board . '/' . $shortcode;

      // Get completed swimlane.
      $completed_lane = Swimlane::getCompletedSwimlanes($shortcode) ;
      if ($completed_lane !== FALSE) {
        $completed_lane = reset($completed_lane);
      }
      else {
        // This is an error condition, but an admin will need to
        // fix it!
        $this->messenger()->addMessage($this->t('There is no completed swimlane for this project. Please contact your system administrator to fix this problem. The task cannot be closed.'));
        $response->addCommand(new RedirectCommand($result));
        $form_state->setResponse($response);
        return $response;
      }

      // Update the task.
      $task
        ->setCompleted(TRUE)
        ->setResolution($resolution_status)
        ->setSwimlane($completed_lane)
        ->save();

      // Issue a TaskClosedEvent event.
      $event = new TaskClosedEvent($task);

      // Get the event_dispatcher service and dispatch the event.
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(TaskClosedEvent::CLOSED, $event);

      // Display message.
      $this->messenger()->addMessage($this->t('Task %ticket_id has been closed.', [
          '%ticket_id' => $ticket_id,
        ]));

      // Reload the board.
      $response->addCommand(new RedirectCommand($result));
      $form_state->setResponse($response);
    }

    return $response;
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function closeModalForm() {
    $command = new CloseModalDialogCommand();
    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
