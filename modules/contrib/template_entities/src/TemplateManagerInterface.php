<?php

namespace Drupal\template_entities;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for managing templates.
 */
interface TemplateManagerInterface {

  /**
   * Returns whether an entity type could be a template or not.
   *
   * @param string $entity_type_id
   *   The entity type id to check.
   *
   * @return bool
   *   TRUE if the entity type could be used as a template, FALSE otherwise.
   */
  public function isEntityTypeTemplateable(string $entity_type_id);

  /**
   * Returns template types (bundle names) for a given entity type.
   *
   * @param string|null $entity_type_id
   *   The entity type id to check.
   *
   * @param string|null $bundle
   *   The bundle id to check.
   *
   * @return \Drupal\template_entities\Entity\TemplateTypeInterface[]
   *   An array of template types keyed by id for the given entity type or an array
   *   of arrays of template types for all entity types that have them keyed
   *   by entity type id.
   */
  public function getTemplateTypesForEntityType(string $entity_type_id = NULL, string $bundle = NULL);

  /**
   * Get template entities which use entity as a template.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\template_entities\Entity\TemplateInterface[]
   */
  public function getTemplatesForEntity(EntityInterface $entity);

  /**
   * Determine whether entity is used as a template.
   *
   * @param $entity_id
   * @param $entity_type_id
   *
   * @return bool
   */
  public function isTemplate($entity_id, $entity_type_id);

  /**
   * Alter queries to hide entities used as templates.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *
   * @return mixed
   */
  public function alterQuery(AlterableInterface $query);

  /**
   * Get all templates for a given entity type.
   *
   * @param string $entity_type_id
   *
   * @return \Drupal\template_entities\Entity\Template[]
   */
  public function getTemplatesForEntityType(string $entity_type_id);

  /**
   * Get all templates for a template type.
   *
   * @param string $template_type_id
   *
   * @return \Drupal\template_entities\Entity\Template[]
   */
  public function getTemplatesOfType(string $template_type_id);

  /**
   * Respond to entity insert hook (new entity has just been saved).
   *
   * @param \Drupal\template_entities\Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function entityInsert(EntityInterface $entity);

  /**
   * Respond to entity presave hook.
   *
   * @param \Drupal\template_entities\Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function entityPresave(EntityInterface $entity);

  /**
   * @param $form
   * @param $form_state
   *
   * @param $template_context
   *
   * @return mixed
   */
  public function alterNewEntityForm(&$form, \Drupal\Core\Form\FormStateInterface $form_state, EntityInterface $entity);
}
