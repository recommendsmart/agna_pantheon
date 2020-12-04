<?php

namespace Drupal\commerce_ticketing\Entity;

use Drupal\commerce_ticketing\CommerceTicketInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the commerce ticket entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_ticket",
 *   label = @Translation("Commerce Ticket"),
 *   label_collection = @Translation("Commerce Tickets"),
 *   bundle_label = @Translation("Commerce Ticket type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_ticketing\Event\TicketEvent",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_ticketing\CommerceTicketListBuilder",
 *     "views_data" = "Drupal\commerce_ticketing\CommerceTicketViewsData",
 *     "storage" = "Drupal\commerce_ticketing\CommerceTicketContentEntityStorage",
 *     "access" = "Drupal\commerce_ticketing\CommerceTicketAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\commerce_ticketing\Form\CommerceTicketForm",
 *       "edit" = "Drupal\commerce_ticketing\Form\CommerceTicketForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "resend-ticket" = "Drupal\commerce_ticketing\Form\CommerceTicketReceiptResendForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_ticketing\CommerceTicketRouteProvider",
 *     }
 *   },
 *   base_table = "commerce_ticket",
 *   admin_permission = "administer commerce_ticketing",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/commerce_ticket/{uuid}",
 *     "add-page" = "/admin/commerce/orders/{commerce_order}/tickets/add",
 *     "resend-ticket-form" = "/admin/commerce/orders/{commerce_order}/{commerce_ticket}/resend",
 *     "collection" = "/admin/commerce/orders/{commerce_order}/tickets",
 *     "add-form" = "/admin/commerce/orders/{commerce_order}/tickets/add/{commerce_ticket_type}",
 *     "edit-form" = "/admin/commerce/orders/{commerce_order}/tickets/{commerce_ticket}/edit",
 *     "delete-form" = "/admin/commerce/orders/{commerce_order}/tickets/{commerce_ticket}/delete",
 *   },
 *   bundle_entity_type = "commerce_ticket_type",
 *   field_ui_base_route = "entity.commerce_ticket_type.edit_form"
 * )
 */
class CommerceTicket extends ContentEntityBase implements CommerceTicketInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_order'] = $this->getOrderId();
    $uri_route_parameters['uuid'] = $this->uuid();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   *
   * When a new commerce ticket entity is created, set the uid entity
   * reference to the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicketNumber() {
    return $this->get('ticket_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTicketNumber($ticket_number) {
    $this->set('ticket_number', $ticket_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function hasOrder() {
    return !empty($this->get('order_id')->target_id) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemId() {
    return $this->get('order_item_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItem() {
    if ($this->getOrderItemId()) {
      return $this->get('order_item_id')->entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntity() {
    if ($this->getOrderItemId()) {
      return $this->get('order_item_id')->entity->getPurchasedEntity();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setDescription(t('The name of the ticket entity.'))
      ->setSettings(
        [
          'max_length' => 50,
          'text_processing' => 0,
        ]
      )
      ->setDefaultValue('')
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => -4,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ticket_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ticket number'))
      ->setDescription(t('The ticket number will be autogenerated based on the selected number pattern.'))
      ->setRequired(FALSE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => -4,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The order backreference
    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setSetting('target_type', 'commerce_order')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // The order item backreference
    $fields['order_item_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order Line Item Id'))
      ->setDescription(t('The parent order item.'))
      ->setSetting('target_type', 'commerce_order_item')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The ticket state.'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'hidden',
          'type' => 'state_transition_form',
          'weight' => 10,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_ticketing\Entity\CommerceTicket', 'getWorkflowId']);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The author.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_order\Entity\Order::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'author',
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the commerce ticket was created.'))
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'timestamp',
          'weight' => 20,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'datetime_timestamp',
          'weight' => 20,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the commerce ticket was last edited.'));

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_ticketing\CommerceTicketInterface $order
   *   The ticket.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(CommerceTicketInterface $ticket) {
    $workflow = CommerceTicketType::load($ticket->bundle())->getWorkflowId();
    return $workflow;
  }

}
