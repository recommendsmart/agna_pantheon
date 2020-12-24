<?php

namespace Drupal\openfarm_holding\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a button "Add your record".
 *
 * @Block(
 *   id = "openidel_holding_add_record",
 *   admin_label = @Translation("Add record button"),
 *   category = @Translation("Openfarm")
 * )
 */
class AddIdeaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Plugin block constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   Current route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    // Create the button that automatically populates
    // the holding reference field in the record.
    $node = $this->currentRouteMatch->getParameter('node');
    $route_parameters = ['node_type' => 'record'];

    // In case of layout builder.
    if (!($node instanceof NodeInterface) || $node->isNew()) {
      return [];
    }

    // If displayed in layout builder node isn't presented.
    if ($node->bundle() == 'holding') {
      $route_parameters['holding'] = $node->id();
    }

    $build = [
      '#cache' => [
        'tags' => $node->getCacheTags(),
        'contexts' => ['user.roles:' . AccountProxyInterface::AUTHENTICATED_ROLE],
      ],
    ];

    // Display "Add Idea" link only for open holdings.
    if ($node->get('field_is_open')->value && $this->currentUser->isAuthenticated()) {
      $build += [
        '#type' => 'link',
        '#title' => $this->t('Add your record'),
        '#url' => Url::fromRoute('node.add', $route_parameters),
        '#attributes' => ['class' => ['add_your_record', 'btn', 'btn-primary']],
      ];
    }

    return $build;
  }

}
