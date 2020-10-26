<?php

namespace Drupal\rocketship_event_core\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Defines the Event entity.
 *
 * @ingroup rocketship_event_core
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Event"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\rocketship_event_core\EventListBuilder",
 *     "views_data" = "Drupal\rocketship_event_core\Entity\EventViewsData",
 *     "translation" = "Drupal\rocketship_event_core\EventTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\rocketship_event_core\Form\EventForm",
 *       "add" = "Drupal\rocketship_event_core\Form\EventForm",
 *       "edit" = "Drupal\rocketship_event_core\Form\EventForm",
 *       "delete" = "Drupal\rocketship_event_core\Form\EventDeleteForm",
 *     },
 *     "access" = "Drupal\rocketship_event_core\EventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\rocketship_event_core\EventHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event",
 *   data_table = "event_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer event entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "date_start",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/event/{event}",
 *     "add-form" = "/admin/structure/event/add",
 *     "edit-form" = "/admin/structure/event/{event}/edit",
 *     "delete-form" = "/admin/structure/event/{event}/delete",
 *     "collection" = "/admin/structure/event",
 *   },
 *   field_ui_base_route = "event.settings"
 * )
 */
class Event extends ContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $start = $date_formatter->format($this->getStartDate(), 'custom', 'd/m/Y H:i');
    $end = $date_formatter->format($this->getEndDate(), 'custom', 'd/m/Y H:i');

    return $start . ' - ' . $end;
  }

  /**
   * {@inheritdoc}
   */
  public function buildDateComponent($date_format, $time_format, $class, $combine_duplicates = TRUE) {
    $dates = $times = [];
    $start = $this->getStartDate();
    $end = $this->getEndDate();
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    $datetime = $date_formatter->format($start, 'custom', DATE_ATOM);
    if (date('d/m/Y', $start) === date('d/m/Y', $end) && $combine_duplicates) {
      $datetime .= '/' . $date_formatter->format($end, 'custom', DATE_ATOM);
    }

    if ($date_format) {
      $start_date = $date_formatter->format($start, 'custom', $date_format);
      $end_date = $date_formatter->format($end, 'custom', $date_format);
      $dates['start'] = $start_date;

      if ($start_date !== $end_date || !$combine_duplicates) {
        $dates['end'] = $end_date;
      }
    }

    if ($time_format) {
      $start_time = $date_formatter->format($start, 'custom', $time_format);
      $end_time = $date_formatter->format($end, 'custom', $time_format);

      $times['start'] = $start_time;

      if ($start_time !== $end_time || !$combine_duplicates) {
        $times['end'] = $end_time;
      }
    }

    // &ndash;.
    return [
      '#theme' => 'event_daterange',
      '#dates' => $dates,
      '#times' => $times,
      '#attributes' => [
        'class' => ['date-component', $class],
        'datetime' => $datetime,
      ],
      '#cache' => [
        'contexts' => ['timezone'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return $this->get('date_start')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate($timestamp) {
    $this->set('date_start', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return $this->get('date_end')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate($timestamp) {
    $this->set('date_end', $timestamp);

    return $this;
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
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $violations = parent::validate();

    // Quick check. Don't want to deal with creating an actual Constraint
    // at the moment.
    $start = $this->getStartDate();
    $end = $this->getEndDate();
    if ($start > $end) {
      $message = t('End date must come after start date');
      $violations->add(new ConstraintViolation($message, $message, [], $end, 'date_end', $end));
    }

    return $violations;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Event entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Event is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['date_start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Event start'))
      ->setDescription(t('When the event starts'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Event end'))
      ->setDescription(t('When the event ends'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
