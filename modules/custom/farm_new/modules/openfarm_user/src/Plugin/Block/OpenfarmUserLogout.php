<?php

namespace Drupal\openfarm_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\openfarm_holding\OpenfarmContextEntityTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'OpenfarmUserLogout' block.
 *
 * @Block(
 *  id = "openfarm_user_logout_block",
 *  admin_label = @Translation("User logout"),
 *   context = {
 *      "user" = @ContextDefinition(
 *       "entity:user",
 *       label = @Translation("Current user"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmUserLogout extends BlockBase implements ContainerFactoryPluginInterface {

  use OpenfarmContextEntityTrait;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxy $accountProxy
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $accountProxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if (($user = $this->getEntity($this->getContexts(), 'user'))
      && $this->currentUser->id() == $user->id()
      && !$this->currentUser->isAnonymous()
    ) {
      $build = [
        'logout' => [
          '#type' => 'link',
          '#title' => $this->t('Log out'),
          '#url' => Url::fromRoute('user.logout', ['user' => $user->id()]),
        ],
      ];

      $build['#cache']['contexts'][] = 'user.roles:authenticated';
    }

    return $build;
  }

}
