<?php

namespace Drupal\openfarm_user\Event;

use Drupal\group\Entity\GroupContent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class OpenfarmUserGroupEvent.
 */
class OpenfarmUserGroupEvent extends Event {

  /**
   * Group owner user.
   *
   * @var \Drupal\user\Entity\User
   */
  public $user;

  /**
   * Group entity which contains the user.
   *
   * @var \Drupal\group\Entity\GroupContent
   */
  public $groupContent;

  /**
   * Node that unite the group.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $node;

  /**
   * OpenfarmUserMentionEvent construct.
   *
   * @param \Drupal\group\Entity\GroupContent $group_content
   *   Group content entity.
   */
  public function __construct(GroupContent $group_content) {
    $this->groupContent = $group_content;

    // When the Record has been deleted, it first removes the gnode group content,
    // so can't get it from group.
    if ($content = $group_content->getGroup()->getContent('group_node:record')) {
      // As one node can have be part of one group get first element.
      // @Todo: check if node can be part more then for one group,
      // and restrict it, if so.
      $this->node = reset($content)->getEntity();
    }
    $this->user = $group_content->getEntity();
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

  /**
   * Get group content entity.
   *
   * @return \Drupal\group\Entity\GroupContent
   *   Group content.
   */
  public function getGroupContent() {
    return $this->groupContent;
  }

  /**
   * Get node that unite the group.
   *
   * @return \Drupal\node\NodeInterface
   *   Node.
   */
  public function getNode() {
    return $this->node;
  }

}
