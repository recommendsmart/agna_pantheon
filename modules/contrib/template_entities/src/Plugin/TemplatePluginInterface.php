<?php

namespace Drupal\template_entities\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\template_entities\Entity\Template;
use Drupal\template_entities\Entity\TemplateType;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines an interface for Template plugin plugins.
 */
interface TemplatePluginInterface extends ConfigurableInterface, PluginInspectionInterface, DerivativeInspectionInterface, PluginFormInterface {

  /**
   * Duplicate an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @param \Drupal\template_entities\Entity\Template $template
   *   The template object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The new duplicate entity.
   */
  public function duplicateEntity(EntityInterface $entity, Template $template);

  /**
   * Get the collection route name.
   *
   * @return string|FALSE
   */
  public function getCollectionLinkTemplate();

  /**
   * Allow plugins to alter tagged queries.
   *
   * It's up to each plugin to determine what alteration needs to be applied.
   * If the query has already been filtered by the entity query decorator, then
   * the "template_entities_filtered" query tag will have been added.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *
   * @param  $template_type
   *
   * @return mixed
   */
  public function selectAlter(SelectInterface $query, TemplateType $template_type);

  /**
   * Allow plugins to alter entity sql queries.
   *
   * It's up to each plugin to determine what alteration needs to be applied.
   * If the query has already been filtered by the entity query decorator, then
   * the "template_entities_filtered" query tag will have been added.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *
   * @param  $template_type
   *
   * @return mixed
   */
  public function entityQueryAlter(SelectInterface $query, TemplateType $template_type);

  /**
   * Get the collection route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *
   * @return \Symfony\Component\Routing\RouteCollection|null A Route instance
   *   or null when not found
   */
  public function getCollectionRoute(RouteCollection $route_collection);

  /**
   * Returns the entity type.
   *
   * @return EntityTypeInterface
   *   The entity type.
   */
  public function getEntityType();

  /**
   * Get an array of bundles that the plugin supports.
   *
   * @return array
   *   Array of bundles that this plugin supports.
   */
  public function getBundleOptions();

  /**
   * Called after template has been used to create and save a new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function duplicateEntityInsert(EntityInterface $entity);

  /**
   * Called after template has been used to create and save a new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function duplicateEntityPresave(EntityInterface $entity);

  /**
   * Alter the new entity form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function alterNewEntityForm(&$form, FormStateInterface $form_state, EntityInterface $entity);
}
