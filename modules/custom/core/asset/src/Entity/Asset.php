<?php

namespace Drupal\asset\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Revision\RevisionableContentEntityBase;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the asset entity.
 *
 * @ingroup asset
 *
 * @ContentEntityType(
 *   id = "asset",
 *   label = @Translation("Asset"),
 *   bundle_label = @Translation("Asset type"),
 *   label_collection = @Translation("Assets"),
 *   label_singular = @Translation("Asset"),
 *   label_plural = @Translation("Assets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count asset",
 *     plural = "@count assets",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\asset\AssetStorage",
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "list_builder" = "\Drupal\asset\AssetListBuilder",
 *     "permission_provider" = "\Drupal\entity\UncacheableEntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\asset\Form\AssetForm",
 *       "edit" = "Drupal\asset\Form\AssetForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *       "delete-multiple" = "\Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "\Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *   },
 *   base_table = "asset",
 *   data_table = "asset_field_data",
 *   revision_table = "asset_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer assets",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "owner" = "uid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "asset_type",
 *   field_ui_base_route = "entity.asset_type.edit_form",
 *   common_reference_target = TRUE,
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/asset/{asset}",
 *     "add-page" = "/asset/add",
 *     "add-form" = "/asset/add/{asset_type}",
 *     "collection" = "/admin/content/asset",
 *     "delete-form" = "/asset/{asset}/delete",
 *     "delete-multiple-form" = "/asset/delete",
 *     "edit-form" = "/asset/{asset}/edit",
 *     "revision" = "/asset/{asset}/revisions/{asset_revision}/view",
 *     "revision-revert-form" = "/asset/{asset}/revisions/{asset_revision}/revert",
 *     "version-history" = "/asset/{asset}/revisions",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 * )
 */
class Asset extends RevisionableContentEntityBase implements AssetInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
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
  public function getArchivedTime() {
    return $this->get('archived')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setArchivedTime($timestamp) {
    $this->set('archived', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleLabel() {
    /** @var \Drupal\asset\Entity\AssetTypeInterface $type */
    $type = \Drupal::entityTypeManager()
      ->getStorage('asset_type')
      ->load($this->bundle());
    return $type->label();
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the asset. Leave this blank to automatically generate a name.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('state')
      ->setLabel(t('Status'))
      ->setDescription(t('Indicates the status of the asset.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\asset\Entity\Asset', 'getWorkflowId']);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the asset.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\asset\Entity\Asset::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 12,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the asset was created.'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getRequestTime')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the asset was last edited.'))
      ->setRevisionable(TRUE);

    $fields['archived'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The time the asset was archived.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\asset\Entity\AssetInterface $asset
   *   The asset entity.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(AssetInterface $asset) {
    $workflow = AssetType::load($asset->bundle())->getWorkflowId();
    return $workflow;
  }

}
