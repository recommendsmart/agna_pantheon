<?php

namespace Drupal\openfarm_statistics\Plugin\Block;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openfarm_holding\OpenfarmContextEntityTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'OpenfarmStatisticsWorkflowBlock' block.
 *
 * @Block(
 *  id = "openfarm_statistics_status",
 *  admin_label = @Translation("Workflow status."),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmStatisticsWorkflowBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use OpenfarmContextEntityTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Content moderation info.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * Constructs a new OpenfarmStatisticsWorkflowBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Block plugin manager.
   * @param \Drupal\content_moderation\ModerationInformation $moderationInformation
   *   Content moderation info.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_manager,
    ModerationInformation $moderationInformation
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->moderationInformation = $moderationInformation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if (!($node = $this->getEntity($this->getContexts()))) {
      return $build;
    }

    $state = $this->moderationInformation->getOriginalState($node);
    if (!$this->configuration['show_all_states']) {
      $build = [
        'container' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['record-statistics-and-status-block--status', $state->id()]],
          'status' => [
            '#type' => 'html_tag',
            '#attributes' => ['class' => ['record-statistics-and-status-block--status__container']],
            '#tag' => 'div',
            '#value' => $state->label(),
          ],
        ],
        '#cache' => [
          'tags' => $node->getCacheTags(),
        ],
      ];
    }
    else {
      /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration $contentModerationPlugin */
      $contentModerationPlugin = $this->entityManager->getStorage('workflow')->load('life_cycle_phases')->getTypePlugin();
      // Hardcoded states groups FE configuration.
      $configurations = [
        [
          'states' => ['draft', 'draft_approval'],
          'label' => $this->t('Prepare'),
          'id' => 'prepare',
        ],
        [
          'states' => ['published'],
          'label' => $this->t('Discuss'),
          'id' => 'discuss',
        ],
        [
          'states' => ['ex', 'needs_work'],
          'label' => $this->t('Refine'),
          'id' => 'refine',
        ],
        [
          'states' => ['postponed'],
          'label' => $this->t('Postponed'),
          'id' => 'postponed',
        ],
        [
          'states' => ['rejected'],
          'label' => $this->t('Rejected'),
          'id' => 'rejected',
        ],
        [
          'states' => ['approved', 'launched'],
          'label' => $this->t('Innovate'),
          'id' => 'innovate',
        ],
      ];

      $list = [];
      // Indicate if it's a last finished group where is state in.
      $last_finished_group = FALSE;
      foreach ($configurations as $id => $configuration) {
        if (in_array($state->id(), $configuration['states'])) {
          $list[$id] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'current_group',
                'record-workflow-full--group-' . $configuration['id'],
              ],
            ],
            'group' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => ['class' => ['record-workflow-full--group-title']],
              '#value' => $configuration['label'],
            ],
            'list' => [
              '#theme' => 'item_list',
            ],
          ];
          $build['#wrapper_attributes']['class'][] = 'record-workflow-full--' . $configuration['id'];

          if ($state->id() == 'postponed' || $state->id() == 'rejected') {
            break;
          }
          $items = [];
          foreach ($configuration['states'] as $available_state) {
            $items[$available_state] = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => ['class' => ['record-workflow-full--group-states']],
              '#value' => $contentModerationPlugin->getState($available_state)->label(),
            ];
            if ($available_state == $state->id()) {
              $items[$available_state]['#attributes']['class'][] = 'record-workflow-full--active-state';
            }
          }

          $list[$id]['list']['#items'] = $items;
          $last_finished_group = TRUE;
        }
        else {
          // Do not need to add rejected and postponed if they are not active.
          if (($state->id() != 'rejected' && in_array('rejected', $configuration['states']))
            || ($state->id() != 'postponed' && in_array('postponed', $configuration['states']))
          ) {
            continue;
          }

          $list[$id] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['record-workflow-full--group-wrapper']],
            'indicator' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => [
                  $last_finished_group ?: 'record-workflow-full--checked',
                  'record-workflow-full--group-' . $configuration['id'],
                  'record-workflow-full--indicator',
                ],
              ],
            ],
            'label' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $configuration['label'],
              '#attributes' => ['class' => ['record-workflow-full--value']],
            ],
          ];
        }
      }

      $build['#wrapper_attributes']['class'][] = 'record-workflow-full';

      $build += [
        '#theme' => 'item_list',
        '#items' => $list,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['show_all_states'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all workflow states'),
      '#default_value' => $this->configuration['show_all_states'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['show_all_states'] = (bool) $form_state->getValue('show_all_states');
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'show_all_states' => FALSE,
    ];
  }

}
