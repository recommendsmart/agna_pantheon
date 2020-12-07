<?php

namespace Drupal\template_entities\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Template entities.
 *
 * @ingroup template_entities
 */
interface TemplateInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Template name.
   *
   * @return string
   *   Name of the Template.
   */
  public function getName();

  /**
   * Sets the Template name.
   *
   * @param string $name
   *   The Template name.
   *
   * @return \Drupal\template_entities\Entity\TemplateInterface
   *   The called Template entity.
   */
  public function setName($name);

  /**
   * Gets the template description.
   *
   * @return string
   *   Description of the template.
   */
  public function getDescription();

  /**
   * Sets the template description.
   *
   * @param string $description
   *   The template description.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setDescription($description);


  /**
   * Gets the Template creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Template.
   */
  public function getCreatedTime();

  /**
   * Sets the Template creation timestamp.
   *
   * @param int $timestamp
   *   The Template creation timestamp.
   *
   * @return \Drupal\template_entities\Entity\TemplateInterface
   *   The called Template entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Template published status indicator.
   *
   * Unpublished Template are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Template is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Template.
   *
   * @param bool $published
   *   TRUE to set this Template to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\template_entities\Entity\TemplateInterface
   *   The called Template entity.
   */
  public function setPublished($published);

  /**
   * Get the entity to use as the template.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The source entity to use as the template.
   */
  public function getSourceEntity();

  /**
   * Get the template plugin used for this template.
   *
   * Convenience method.
   *
   * @return \Drupal\template_entities\Plugin\TemplatePluginInterface
   *   The template plugin associated with the template type.
   */
  public function getTemplatePlugin();

  /**
   * Get the destination to redirect to after a new entity has been created
   * from a template.
   *
   * @return mixed
   */
  public function getDestinationAfterNewFromTemplate();

}
