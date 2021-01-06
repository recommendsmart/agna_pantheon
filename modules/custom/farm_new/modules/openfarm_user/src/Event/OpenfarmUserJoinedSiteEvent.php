<?php

namespace Drupal\openfarm_user\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class OpenfarmUserJoinedSiteEvent.
 */
class OpenfarmUserJoinedSiteEvent extends Event {

  /**
   * User that joined the site.
   *
   * @var \Drupal\user\Entity\User
   */
  public $user;

  /**
   * OpenfarmUserJoinedSiteEvent construct.
   *
   * @param \Drupal\user\UserInterface $user
   *   Group content entity.
   */
  public function __construct(UserInterface $user) {
    $this->user = $user;
  }

  /**
   * Get user.
   *
   * @return \Drupal\user\Entity\User
   *   User.
   */
  public function getUser() {
    return $this->user;
  }

}
