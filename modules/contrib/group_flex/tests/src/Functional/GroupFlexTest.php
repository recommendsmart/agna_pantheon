<?php

namespace Drupal\Tests\group_flex\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the behavior of the group type form.
 *
 * @group group
 */
class GroupFlexTest extends GroupBrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_test_config',
    'group_flex',
    'group_permissions',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The normal user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * Disabled config schema checking temporarily until all errors are resolved.
   *
   * @var int
   *
   * @todo Fix this properly, see https://www.drupal.org/node/2391795.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access group overview',
      'create default group',
      'create other group',
      'administer group',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->account = $this->createUser([
      'administer group',
      'create default group',
    ]);
    $this->group = $this->createGroup(['uid' => $this->account->id()]);
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Add permissions to members of the group.
    $role = $this->group->getGroupType()->getMemberRole();
    $role->grantPermissions(['edit group']);
    $role->save();

  }

  /**
   * Tests group flex.
   */
  public function testGroupFlexGroupType() {
    $this->drupalLogin($this->account);

    // Make sure by default it is not enabled.
    $this->drupalGet('/group/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Visibility');

    // Now change the settings to enabled and public.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure now it is enabled and default value Public..
    $this->drupalGet('/group/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Visibility');
    $this->assertSession()->pageTextContains('visibility is Public');

    // Now change the settings to enabled and private.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GROUP_FLEX_TYPE_VIS_PRIVATE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure now the default value is Private.
    $this->drupalGet('/group/1/edit');
    $this->assertSession()->pageTextContains('visibility is Private');

    // Now change the settings to enabled and flexible.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GROUP_FLEX_TYPE_VIS_FLEX);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure now the default value is Public and field enabled again.
    $this->drupalGet('/group/1/edit');
    $this->assertSession()->fieldEnabled('group_visibility');
    $this->assertSession()->fieldValueEquals('group_visibility', GROUP_FLEX_TYPE_VIS_PUBLIC);

  }

  /**
   * Tests group flex.
   */
  public function testGroupFlexGroupFunctionality() {
    $this->drupalLogin($this->groupCreator);

    // Now create a flexible type group.
    $this->drupalGet('/admin/group/types/add');
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Flexible group');
    $page->fillField('id', 'flexible_group');
    $page->selectFieldOption('creator_wizard', FALSE);
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GROUP_FLEX_TYPE_VIS_FLEX);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/admin/group/types/manage/flexible_group/permissions');

    $this->drupalLogout();
    $this->drupalLogin($this->createUser(['create flexible_group group']));

    // Make sure now the default value is Public and field enabled again.
    $this->drupalGet('/group/add/flexible_group');
    $this->assertSession()->fieldEnabled('group_visibility');
    $this->assertSession()->fieldValueEquals('group_visibility', GROUP_FLEX_TYPE_VIS_PUBLIC);
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Flex group - public');
    $this->submitForm([], 'edit-submit');

    // Make sure now the default value is Private and field enabled again.
    $this->drupalGet('/group/add/flexible_group');
    $this->assertSession()->fieldEnabled('group_visibility');
    $page->selectFieldOption('group_visibility', GROUP_FLEX_TYPE_VIS_PRIVATE);
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Flex group - private');
    $this->submitForm([], 'edit-submit');
    $this->assertSession()->pageTextContains('Flex group - private');

    $this->drupalLogout();

    $user2 = $this->createUser(['access group overview']);
    $this->drupalLogin($user2);

    $this->drupalGet('/admin/group');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Groups');
    $this->assertSession()->pageTextContains('Flex group - public');
    $this->assertSession()->pageTextNotContains('Flex group - private');

  }

  /**
   * Creates a new group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The created group entity.
   */
  protected function createGroupType(array $values = []) {
    $defaultValues = [
      'label' => $this->randomMachineName(),
    ];
    $values = array_merge($defaultValues, $values);
    $groupType = $this->entityTypeManager->getStorage('group_type')->create($values);
    $groupType->enforceIsNew();
    $groupType->save();
    return $groupType;
  }

  /**
   * Creates a new group.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type of the new entity.
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroupOfType(GroupTypeInterface $groupType, array $values = []) {
    $defaultValues = [
      'label' => $this->randomMachineName(),
    ];
    $values = array_merge($defaultValues, $values);
    $group = $this->entityTypeManager->getStorage('group')->create($values);
    $group->enforceIsNew();
    $group->save();
    return $group;
  }

}
