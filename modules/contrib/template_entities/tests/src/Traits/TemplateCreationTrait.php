<?php

namespace Drupal\Tests\template_entities\Traits;

use Drupal;
use Drupal\template_entities\Entity\Template;
use Drupal\user\Entity\User;

/**
 * Provides methods to create templates based on default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait TemplateCreationTrait {

  /**
   * Creates a template based on default settings.
   *
   * @param array $values
   *   (optional) An associative array of values for the template, as used in
   *   creation of entity. Override the defaults by specifying the key and value
   *   in the array, for example:
   *
   * @return \Drupal\template_entities\Entity\TemplateInterface
   *   The created template entity.
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @code
   *     $this->drupalCreateTemplate(array(
   *       'label' => t('Hello, world!'),
   *       'type' => 'page_template',
   *       'name' => 'Page template 1',
   *       'description' => 'A page template.',
   *       'template_entity_id' => 12,
   *       'user_id' => 1,
   *     ));
   * @endcode
   *   The following defaults are provided:
   *   - body: Random string using the default filter format:
   * @code
   *       $values['body'][0] = array(
   *         'value' => $this->randomMachineName(32),
   *         'format' => filter_default_format(),
   *       );
   * @endcode
   *   - name: Random string.
   *   - description: Random string.
   *   - type: 'page_template'.
   *   - uid: The currently logged in user, or anonymous.
   *
   */
  protected function createTemplate(array $values = []) {
    // Populate defaults array.
    $values += [
      'name' => $this->randomMachineName(8),
      'description' => $this->randomMachineName(30),
    ];

    if (!array_key_exists('uid', $values)) {
      $user = User::load(Drupal::currentUser()->id());
      if ($user) {
        $values['uid'] = $user->id();
      }
      elseif (method_exists($this, 'setUpCurrentUser')) {
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->setUpCurrentUser();
        $values['uid'] = $user->id();
      }
      else {
        $values['uid'] = 0;
      }
    }

    $template = Template::create($values);
    $template->save();

    return $template;
  }

}
