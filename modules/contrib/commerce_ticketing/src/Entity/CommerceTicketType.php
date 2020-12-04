<?php

namespace Drupal\commerce_ticketing\Entity;

use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Commerce Ticket type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_ticket_type",
 *   label = @Translation("Commerce Ticket type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_ticketing\Form\CommerceTicketTypeForm",
 *       "edit" = "Drupal\commerce_ticketing\Form\CommerceTicketTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\commerce_ticketing\CommerceTicketTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer commerce ticket types",
 *   bundle_of = "commerce_ticket",
 *   config_prefix = "commerce_ticket_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/commerce_ticket_types/add",
 *     "edit-form" = "/admin/structure/commerce_ticket_types/manage/{commerce_ticket_type}",
 *     "delete-form" = "/admin/structure/commerce_ticket_types/manage/{commerce_ticket_type}/delete",
 *     "collection" = "/admin/structure/commerce_ticket_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "numberPattern",
 *     "workflow",
 *   }
 * )
 */
class CommerceTicketType extends ConfigEntityBundleBase {

  /**
   * The machine name of this commerce ticket type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the commerce ticket type.
   *
   * @var string
   */
  protected $label;

  /**
   * The number pattern ID.
   *
   * @var string
   */
  protected $numberPattern;

  /**
   * The order type workflow ID.
   *
   * @var string
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  public function getNumberPattern() {
    if ($this->numberPattern) {
      return NumberPattern::load($this->numberPattern);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPatternId() {
    return $this->numberPattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setNumberPatternId($number_pattern_id) {
    $this->numberPattern = $number_pattern_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($workflow_id) {
    $this->workflow = $workflow_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // The order type must depend on the module that provides the workflow.
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflow = $workflow_manager->createInstance($this->getWorkflowId());
    $this->calculatePluginDependencies($workflow);

    return $this;
  }

}
