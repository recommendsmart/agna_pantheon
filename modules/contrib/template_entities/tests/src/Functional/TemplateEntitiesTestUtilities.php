<?php

namespace Drupal\Tests\template_entities\Functional;

use Drupal;
use Drupal\Tests\template_entities\Traits\TemplateCreationTrait;
use Drupal\Tests\template_entities\Traits\TemplateTypeCreationTrait;


/**
 * Utility methods for use in BrowserTestBase tests.
 *
 * This trait will not work if not used in a child of BrowserTestBase.
 */
trait TemplateEntitiesTestUtilities {

  use TemplateTypeCreationTrait;
  use TemplateCreationTrait;


  /**
   * Creates a new Template through the UI.
   *
   * @param string $template_type_id
   *   The id of the template type.
   * @param string $name
   *   The name of the template to create.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use as the template.
   *
   * @return \Drupal\template_entities\Entity\TemplateInterface
   *   The template that was just created.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createTemplateThroughUi($template_type_id, $name, $entity) {
    $this->drupalPostForm('/admin/structure/templates/template/add/' . $template_type_id, [
      'name[0][value]' => $name,
      'template_entity_id[0][target_id]' => $entity->label() . ' (' . $entity->id() . ')',
    ], 'Save');

    /** @var \Behat\Mink\Session $session */
    $session = $this->getSession();

    $page = $session->getPage();
    $this->assert($page->hasContent("Created the {$name} Template."), 'New template created');

    /** @var \Drupal\template_entities\Entity\TemplateInterface $template */
    $template = $this->getOneEntityByLabel('template', $name);
    return $template;
  }

  /**
   * Loads a single entity by its label.
   *
   * The UI approach to creating an entity doesn't make it easy to know what
   * the ID is, so this lets us make paths for an entity after it's created.
   *
   * @param string $type
   *   The type of entity to load.
   * @param string $label
   *   The label of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOneEntityByLabel($type, $label) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = Drupal::service('entity_type.manager');
    $property = $entity_type_manager->getDefinition($type)->getKey('label');
    $entity_list = $entity_type_manager->getStorage($type)
      ->loadByProperties([$property => $label]);
    $entity = current($entity_list);
    if (!$entity) {
      $this->fail("No {$type} entity named {$label} found.");
    }

    return $entity;
  }

  /**
   * Creates a new node from a template through the UI.
   *
   * @param $template_id
   * @param string $title
   *   The title of the new node.
   *
   * @return \Drupal\node\Entity\Node
   *   The node that was just created.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createNodeFromTemplateThroughUi($template_id, $title) {
    $this->drupalPostForm('/node/add/page/template/' . $template_id, [
      'title[0][value]' => $title,
    ], 'Save');

    /** @var \Behat\Mink\Session $session */
    $session = $this->getSession();

    $page = $session->getPage();
    $this->assert($page->hasContent($title), 'New node created');

    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getOneEntityByLabel('node', $title);
    return $node;
  }

  /**
   * Creates a node by "clicking" buttons.
   *
   * @param string $label
   *   The label of the Node to create.
   * @param string $bundle
   *   The bundle of the Node to create.
   * @param bool $publish
   *   The publishing status to set.
   *
   * @return \Drupal\node\NodeInterface
   *   The Node that was just created.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createNodeThroughUi($label, $bundle, $publish = TRUE) {
    $this->drupalGet('/node/add/' . $bundle);

    /** @var \Behat\Mink\Session $session */
    $session = $this->getSession();
    $this->assertSession()->statusCodeEquals(200);

    $page = $session->getPage();
    $page->fillField('Title', $label);
    if ($publish) {
      $page->findButton('Save')->click();
    }
    else {
      $page->uncheckField('Published');
      $page->findButton('Save')->click();
    }

    $session->getPage()->hasContent("{$label} has been created");

    return $this->getOneEntityByLabel('node', $label);
  }

  /**
   * Determine if the content list has an entity's label.
   *
   * This assertion can be used to validate a particular entity exists in the
   * current workspace.
   *
   * @param $label
   *
   * @return boolean
   */
  protected function isLabelInContentOverview($label) {
    $this->drupalGet('/admin/content');
    $session = $this->getSession();
    $this->assertSession()->statusCodeEquals(200);
    $page = $session->getPage();
    return $page->hasContent($label);
  }

}
