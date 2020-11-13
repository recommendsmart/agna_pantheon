<?php

namespace Drupal\burndown\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\burndown\Entity\Sprint;
use Drupal\burndown\Entity\Swimlane;
use Drupal\burndown\Entity\Project;
use Drupal\burndown\Entity\Task;

/**
 * Form controller for Task edit forms.
 *
 * @ingroup burndown
 */
class TaskForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\burndown\Entity\Task $entity */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'hidden',
        '#title' => $this->t('Create new revision'),
        '#default_value' => TRUE,
        '#weight' => 10,
      ];

      // When we AJAX load the task edit form, we need to properly
      // close it after.
      $params = \Drupal::request()->query->all();
      if (isset($params['_wrapper_format']) &&
        (($params['_wrapper_format'] == 'drupal_ajax') ||
        ($params['_wrapper_format'] == 'drupal_modal'))) {
        $form['actions']['submit']['#submit'][] = '_burndown_task_ajax_submit';
        $form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
      }
    }

    // Set estimate options based on project.
    $task = $form_state->getformObject()->getEntity();
    $project = $task->getProject();
    $options = $project->getEstimateSizes();

    // Remove delete button (too easy to accidentally press).
    unset($form['actions']['delete']);

    if (empty($options)) {
      $form['estimate']['#access'] = FALSE;
    }
    else {
      // Put standard 'none' option at the top.
      $none_option = [
        '_none' => "- None -",
      ];
      $options = array_merge($none_option, $options);

      // Update the form widget.
      $form['estimate']['widget']['#options'] = $options;
    }

    // Close the images section by default to save space.
    $form['images']['widget']['#open'] = FALSE;

    // Add our add/remove from watchlist link.
    if (!$this->entity->isNew()) {
      $user = \Drupal::currentUser();
      $on_list = $task->checkIfOnWatchlist($user);
      if ($on_list !== FALSE) {
        $class = 'watch';
        $link = Link::createFromRoute(t('Stop watching this task'),
          'burndown.task_remove_from_watchlist',
          [
            'ticket_id' => $task->getTicketID(),
            'user_id' => $user->id(),
          ],
          ['absolute' => TRUE]
        );
      }
      else {
        $class = 'mute';
        $link = Link::createFromRoute(t('Watch this task'),
          'burndown.task_add_to_watchlist',
          [
            'ticket_id' => $task->getTicketID(),
            'user_id' => $user->id(),
          ],
          ['absolute' => TRUE]
        );
      }

      $link = $link->toRenderable();
      $link = render($link);
      $link = (String) $link;

      $form['watchlist_link'] = [
        '#prefix' => '<div class="watch_list ' . $class . '">',
        '#suffix' => '</div>',
        '#markup' => $link,
        '#weight' => -10,
      ];
    }

    // Reopen link for closed tasks.
    if ($task->getCompleted() == TRUE) {
      $link = Link::createFromRoute(t('Reopen this task'),
          'burndown.reopen_task',
          [
            'ticket_id' => $task->getTicketID(),
          ],
          ['absolute' => TRUE]
        );

      $link = $link->toRenderable();
      $link = render($link);
      $link = (String) $link;

      $form['reopen_task'] = [
        '#prefix' => '<div class="reopen_task">',
        '#suffix' => '</div>',
        '#markup' => $link,
        '#weight' => -9,
      ];
    }

    // Clean up links widget.
    $form['link']['#type'] = 'details';
    $form['link']['#title'] = t('Link(s)');
    $form['link']['#open'] = FALSE;

    if (!$this->entity->isNew()) {
      // Clean up related to widget.
      $form['relationships']['#type'] = 'details';
      $form['relationships']['#title'] = t('Related to');
      $form['relationships']['#open'] = FALSE;
      $form['relationships']['widget']['#title'] = t('Tasks that this task is related to:');
      $form['relationships']['widget']['#access'] = FALSE;
      $form['relationships']['list'] = [
        '#markup' => '<div id="relationships_list"></div>',
      ];
      $form['relationships']['add_new'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'add_relationship',
        ],
      ];
      $form['relationships']['add_new']['to_task'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Task'),
        '#target_type' => 'burndown_task',
        '#tags' => TRUE,
        '#size' => 15,
        '#maxlength' => 25,
      ];
      $form['relationships']['add_new']['type'] = [
        '#type' => 'select',
        '#title' => t('Relationship Type'),
        '#options' => Task::getRelationshipTypes(),
      ];
      $form['relationships']['add_new']['link'] = [
        '#markup' => '<a href="#" class="button add_relationship" data-ticket-id="' . $task->getTicketID() . '">Add Relationship</a>',
      ];

      // Hide miscellaneous items.
      $form['ticket_id']['#access'] = FALSE;
      $form['project']['#access'] = FALSE;
      $form['swimlane']['#access'] = FALSE;
      $form['revision_log']['#access'] = FALSE;
      $form['status']['#access'] = FALSE;
      $form['sprint']['#access'] = FALSE;
      $form['backlog_sort']['#access'] = FALSE;
      $form['board_sort']['#access'] = FALSE;
      $form['watch_list']['#access'] = FALSE;
      $form['completed']['#access'] = FALSE;
      $form['resolution']['#access'] = FALSE;
      $form['log']['#access'] = FALSE;
    }

    //-----------------------
    // Add our log container
    //-----------------------
    if (!$this->entity->isNew()) {
      // Add a placeholder for the log section.
      $form['log'] = [
        '#type' => 'details',
        '#title' => t('Log'),
        '#weight' => 35,
      ];
      // "Tabs" to load different types.
      $form['log']['tabs'] = [
        '#prefix' => '<div class="log_tabs">',
        '#suffix' => '</div>',
      ];
      $form['log']['tabs']['comment'] = [
        '#markup' => '<a href="#" class="comment">Comments</a>',
      ];
      $form['log']['tabs']['changed'] = [
        '#markup' => '<a href="#" class="changed">Changes</a>',
      ];
      $form['log']['tabs']['work'] = [
        '#markup' => '<a href="#" class="work">Work Logs</a>',
      ];
      $form['log']['tabs']['all'] = [
        '#markup' => '<a href="#" class="all">All</a>',
      ];

      // Container for ajax-loaded logs.
      $form['log']['container'] = [
        '#prefix' => '<div id="burndown_task_log" data-ticket-id="' . $task->getTicketId() . '">',
        '#suffix' => '</div>',
      ];

      // Comment form.
      $form['log']['comment'] = [
        '#type' => 'container',
        '#title' => t('Add a comment'),
        '#attributes' => [
          'class' => 'add_comment',
        ],
      ];
      $form['log']['comment']['body'] = [
        '#type' => 'textarea',
      ];
      $form['log']['comment']['link'] = [
        '#markup' => '<a href="#" class="button">Add Comment</a>',
      ];

      // Work log form.
      $form['log']['work'] = [
        '#type' => 'container',
        '#title' => t('Add a work log'),
        '#attributes' => [
          'class' => 'add_work',
        ],
      ];
      $form['log']['work']['body'] = [
        '#type' => 'textarea',
        '#title' => t('Comment'),
      ];
      $form['log']['work']['quantity'] = [
        '#type' => 'number',
        '#title' => t('Time'),
        '#min' => 0,
        '#default_value' => 0,
      ];
      $form['log']['work']['quantity_type'] = [
        '#type' => 'select',
        '#options' => [
          'm' => t('Minutes'),
          'h' => t('Hours'),
          'd' => t('Days'),
        ],
        '#default_value' => 'h',
      ];
      $form['log']['work']['link'] = [
        '#markup' => '<a href="#" class="button">Add Work Log</a>',
      ];
    }

    // Add "assign to me" link.
    $form['assigned_to']['widget'][0]['assign_to_me'] = [
      '#markup' => '<a href="#" class="assign_to_me">Assign to me</a>',
    ];

    // Attach library.
    $form['#attached']['library'][] = 'burndown/drupal.burndown.task_edit';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    // Remove artificial fields from form values, as these aren't part of the
    // expected set of values.
    unset($form['relationships']);
    unset($form['log']);

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Task.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Task.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.burndown_task.canonical', ['burndown_task' => $entity->id()]);
  }

}
