<?php

namespace Drupal\entity_inherit;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Error;
use Drupal\entity_inherit\EntityInheritDev\EntityInheritDev;
use Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntityFactory;
use Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntitySingleInterface;
use Drupal\entity_inherit\EntityInheritEntity\EntityInheritSingleExistingEntityInterface;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldFactory;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueFactory;
use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginCollection;
use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginManager;
use Drupal\entity_inherit\EntityInheritQueue\EntityInheritQueue;
use Drupal\entity_inherit\EntityInheritStorage\EntityInheritStorage;
use Drupal\entity_inherit\EntityInheritStorage\EntityInheritStorageInterface;

/**
 * EntityInherit singleton. Use \Drupal::service('entity_inherit').
 */
class EntityInherit {

  use StringTranslationTrait;

  /**
   * The config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The plugin manager service.
   *
   * @var \Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginManager
   */
  protected $pluginManager;

  /**
   * Injected entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The injected logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The injected config service.
   * @param \Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginManager $plugin_manager
   *   The injected plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The injected entity type manager.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The injected messenger service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The injected entity field manager.
   * @param \Drupal\Core\State\State $state
   *   The state service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   The logger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityInheritPluginManager $plugin_manager, EntityTypeManager $entity_type_manager, Messenger $messenger, EntityFieldManager $entity_field_manager, State $state, LoggerChannelFactory $loggerFactory) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->entityFieldManager = $entity_field_manager;
    $this->state = $state;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Get all field names as an array of strings.
   *
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return array
   *   All field names.
   */
  public function allFields(string $category) : array {
    $return = [];
    foreach ($this->entityFieldManager->getFieldMap() as $fields) {
      foreach ($fields as $fieldname => $field) {
        $return[$fieldname] = $field;
      }
    }
    $original = $return;
    $this->plugins()->filterFields($return, $original, $category, $this);
    return $return;
  }

  /**
   * Get all field names as an array of strings, for a bundle.
   *
   * @param string $type
   *   A type such as "node".
   * @param string $bundle
   *   A type such as "page".
   *
   * @return array
   *   All field names for bundle.
   */
  public function bundleFieldNames(string $type, string $bundle) : array {
    $candidates = $this->getFieldDefinitions($type, $bundle);
    $filtered = $candidates;

    $this->plugins()->filterFields($filtered, $candidates, 'inheritable', $this);

    return $filtered;
  }

  /**
   * Gets configuration from the "entity_inherit" config store.
   *
   * @param string $name
   *   The config variable name.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function configGet(string $name, $default = NULL) {
    return $this->configFactory->get('entity_inherit.general.settings')->get($name);
  }

  /**
   * Gets array configuration from the "entity_inherit" config store.
   *
   * @param string $name
   *   The config variable name.
   * @param array $default
   *   The default value.
   *
   * @return array
   *   The value, or default .
   */
  public function configGetArray(string $name, array $default = []) : array {
    $candidate = $this->configGet($name, $default);

    if (!is_array($candidate)) {
      $this->userErrorMessage($this->t('The @name config paramter should contain an array, but it contains a @type. Assuming the default requested value.', [
        '@name' => $name,
        '@type' => gettype($candidate),
      ]));
      return $default;
    }

    return $candidate;
  }

  /**
   * Get the Development singleton.
   *
   * @return \Drupal\entity_inherit\EntityInheritDev\EntityInheritDev
   *   The entity factory singleton.
   */
  public function dev() : EntityInheritDev {
    return $this->singleton(EntityInheritDev::class);
  }

  /**
   * Get the Entity factory.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntityFactory
   *   The entity factory singleton.
   */
  public function getEntityFactory() : EntityInheritEntityFactory {
    return $this->singleton(EntityInheritEntityFactory::class);
  }

  /**
   * Get the Entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManager
   *   The entity field manager.
   */
  public function getEntityFieldManager() : EntityFieldManager {
    return $this->entityFieldManager;
  }

  /**
   * Get the Entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManager
   *   The entity type manager.
   */
  public function getEntityTypeManager() : EntityTypeManager {
    return $this->entityTypeManager;
  }

  /**
   * Get all field definitions, if possible, for a type and bundle.
   *
   * @param string $type
   *   An entity type.
   * @param string $bundle
   *   An entity bundle.
   *
   * @return array
   *   Field definitions, or an empty array if not possible.
   */
  public function getFieldDefinitions(string $type, string $bundle) : array {
    $type_definitions = $this->getEntityTypeManager()->getDefinitions();

    if (!array_key_exists($type, $type_definitions) || !is_a($type_definitions[$type], ContentEntityType::class)) {
      // This will avoid a "LogicException with message 'Getting the base
      // fields is not supported for entity type Menu.'" error.
      return [];
    }

    return $this->getEntityFieldManager()->getFieldDefinitions($type, $bundle);
  }

  /**
   * Get the field value factory.
   *
   * @return \Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueFactory
   *   The field value factory singleton.
   */
  public function getFieldValueFactory() : EntityInheritFieldValueFactory {
    return $this->singleton(EntityInheritFieldValueFactory::class);
  }

  /**
   * Get the Queue singleton.
   *
   * @return \Drupal\entity_inherit\EntityInheritQueue\EntityInheritQueue
   *   The Queue singleton.
   */
  public function getQueue() : EntityInheritQueue {
    return $this->singleton(EntityInheritQueue::class);
  }

  /**
   * Get the storage manager class.
   *
   * @return \Drupal\entity_inherit\EntityInheritStorage\EntityInheritStorageInterface
   *   The storage singleton.
   */
  public function getStorage() : EntityInheritStorageInterface {
    return $this->singleton(EntityInheritStorage::class);
  }

  /**
   * Get all fields which are potentially inheritable for a bundle.
   *
   * @param string $type
   *   An entity type.
   * @param string $bundle
   *   An entity bundle.
   *
   * @return array
   *   Array of fields.
   */
  public function inheritableFields($type, $bundle) : array {
    $all_fields = $this->allFields('inheritable');
    $my_fields = $this->getFieldDefinitions($type, $bundle);
    $return = [];
    foreach ($all_fields as $id => $candidate) {
      if (array_key_exists($id, $my_fields)) {
        $return[$id] = $candidate;
      }
    }
    return $return;
  }

  /**
   * Helper function to display feedback about parent fields.
   *
   * @return array
   *   An array with two keys:
   *   * severity can be 2 (REQUIREMENT_ERROR), 1 (REQUIREMENT_WARNING),
   *     0 (REQUIREMENT_OK).
   *   * translated_message is the actual message to display
   */
  public function parentFieldFeedback() : array {
    $return = [
      'severity' => 2,
      'translated_message' => 'coud not fetch field feedback',
    ];
    try {
      $all_fields = $this->getParentEntityFields();
      $valid = $all_fields->validOnly('parent');
      $invalid = $all_fields->invalidOnly('parent');

      if (count($invalid)) {
        $return['translated_message'] = $this->formatPlural(count($invalid), 'The following field is invalid (either it does not exist or is not an entity reference field): @f', 'The following fields are invalid (either they do not exist or are not entity reference fields): @f', [
          '@f' => implode(', ', $invalid->toArray()),
        ]);
      }
      elseif (count($valid)) {
        $return['severity'] = 0;
        $return['translated_message'] = $this->formatPlural(count($valid), '@c valid field is defined', '@c valid fields are defined', [
          '@c' => count($valid),
        ]);
      }
      else {
        $return['severity'] = 1;
        $return['translated_message'] = $this->t("No fields are defined. For this module to work, you need to define at least one reference field which will be a parent's entity, then paste the field name here.");
      }
    }
    catch (\Throwable $t) {
      $return['translated_message'] = $t;
    }

    return $return;
  }

  /**
   * Display feedback on whether the fields are valid or not.
   */
  public function displayParentEntityFieldsValidityFeedback() {
    $parent_field_feedback = $this->parentFieldFeedback();
    $message = $parent_field_feedback['translated_message'];
    switch ($parent_field_feedback['severity']) {
      case 2:
        $this->userErrorMessage($message);
        break;

      case 1:
        $this->userWarningMessage($message);
        break;

      default:
        $this->userStatusMessage($message);
        break;
    }
  }

  /**
   * Get the EntityInheritFieldFactory singleton.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldFactory
   *   A factory for a list of fields.
   */
  public function entityFieldListFactory() : EntityInheritFieldFactory {
    return $this->singleton(EntityInheritFieldFactory::class);
  }

  /**
   * Get the messenger service.
   *
   * @return \Drupal\Core\Messenger\Messenger
   *   The messenger.
   */
  public function getMessenger() : Messenger {
    return $this->messenger;
  }

  /**
   * Get the fields where parents are stored.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   A list of fields where the parent entities are stored.
   */
  public function getParentEntityFields() : EntityInheritFieldListInterface {
    return $this->entityFieldListFactory()->fromArray($this->configGetArray('fields'));
  }

  /**
   * Testable implementation of hook_presave().
   */
  public function hookPresave(EntityInterface $entity) {
    try {
      $wrapped_entity = $this->wrap($entity);
      $wrapped_entity->presave();
      $this->plugins()->presave($wrapped_entity, $this);
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      $this->userErrorMessage($this->t('Entity Inherit encountered an error. See the log for details.'));
    }
  }

  /**
   * Testable implementation of hook_requirements().
   */
  public function hookRequirements(string $phase) : array {
    $requirements['entity_inherit'] = [
      'title' => $this->t('Entity Inherit'),
      'description' => $this->t('We need at least one valid parent field, and no invalid fields, at /admin/config/entity_inherit'),
    ];

    try {
      $feedback = $this->parentFieldFeedback();

      $requirements['entity_inherit'] += [
        'value' => $feedback['translated_message'],
        'severity' => $feedback['severity'],
      ];
    }
    catch (\Throwable $t) {
      $requirements['entity_inherit'] += [
        'value' => $t,
        'severity' => REQUIREMENT_ERROR,
      ];
    }

    return $requirements;
  }

  /**
   * Get the plugin manager service.
   *
   * @return \Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginManager
   *   The plugin manager.
   */
  public function getPluginManager() : EntityInheritPluginManager {
    return $this->pluginManager;
  }

  /**
   * Get all EntityInerit plugins.
   *
   * See the modules included in the ./modules directory for an example on how
   * to create a plugin.
   *
   * @return \Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginCollection
   *   All plugins.
   *
   * @throws \Exception
   */
  public function plugins() : EntityInheritPluginCollection {
    return $this->singleton(EntityInheritPluginCollection::class);
  }

  /**
   * Set the fields where parents are stored.
   *
   * @param array $fields
   *   An array of field names.
   */
  public function setParentEntityFields(array $fields) {
    $this->configFactory->getEditable('entity_inherit.general.settings')->set('fields', $fields)->save();
  }

  /**
   * Create a singleton.
   *
   * @param string $class
   *   Class corresponding to the singleton we want.
   *
   * @return mixed
   *   A singleton object.
   */
  public function singleton(string $class) {
    static $singleton = [];

    if (!array_key_exists($class, $singleton)) {
      $singleton[$class] = new $class($this);
    }

    return $singleton[$class];
  }

  /**
   * Set an array in a state variable.
   *
   * @param string $variable
   *   A state variable name.
   * @param array $value
   *   A state variable value.
   */
  public function stateSetArray(string $variable, array $value) {
    $this->state->set($variable, $value);
  }

  /**
   * Get an array from a state variable.
   *
   * @param string $variable
   *   A state variable name.
   * @param array $default
   *   A default value if the state variable is not an array or is not set.
   *
   * @return array
   *   A state variable value, or an empty array
   */
  public function stateGetArray(string $variable, array $default = []) : array {
    $candidate = $this->state->get($variable, $default);

    return is_array($candidate) ? $candidate : $default;
  }

  /**
   * Create a field list object.
   *
   * @param array $field_names
   *   Field names.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   A list of fields.
   */
  public function toFieldList(array $field_names) {
    return $this->entityFieldListFactory()->fromArray($field_names);
  }

  /**
   * Display an error to the user.
   *
   * @param string $translated_message
   *   A translated message.
   */
  public function userErrorMessage(string $translated_message) {
    $this->messenger->addError($translated_message);
  }

  /**
   * Display a warning to the user.
   *
   * @param string $translated_message
   *   A translated message.
   */
  public function userWarningMessage(string $translated_message) {
    $this->messenger->addWarning($translated_message);
  }

  /**
   * Display a message to the user.
   *
   * @param string $translated_message
   *   A translated message.
   */
  public function userStatusMessage(string $translated_message) {
    $this->messenger->addStatus($translated_message);
  }

  /**
   * Whether or not a field name is a valid parent field.
   *
   * @param string $field_name
   *   A field name.
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return bool
   *   Whether or not a field name is valid.
   */
  public function validFieldName(string $field_name, string $category) : bool {
    return in_array($field_name, array_keys($this->allFields($category)));
  }

  /**
   * Log a \Throwable to the watchdog.
   *
   * Modeled after Core's watchdog_exception().
   *
   * @param \Throwable $t
   *   A \throwable.
   * @param mixed $message
   *   The message to store in the log. If empty, a text that contains all
   *   useful information about the passed-in exception is used.
   * @param mixed $variables
   *   Array of variables to replace in the message on display or NULL if
   *   message is already translated or not possible to translate.
   * @param mixed $severity
   *   The severity of the message, as per RFC 3164.
   * @param mixed $link
   *   A link to associate with the message.
   */
  public function watchdogThrowable(\Throwable $t, $message = NULL, $variables = [], $severity = RfcLogLevel::ERROR, $link = NULL) {

    // Use a default value if $message is not set.
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }

    if ($link) {
      $variables['link'] = $link;
    }

    $variables += Error::decodeException($t);

    $this->loggerFactory->get('entity_inherit')->log($severity, $message, $variables);
  }

  /**
   * Wrap a Drupal entity into our own class for processings.
   *
   * This entity can be in the process of creation, i.e. not have an id and
   * not exist in the database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntitySingleInterface
   *   Our wrapper around a Drupal entity.
   */
  public function wrap(EntityInterface $entity) : EntityInheritEntitySingleInterface {
    return $this->getEntityFactory()->fromEntity($entity);
  }

  /**
   * Wrap an existing Drupal entity into our own class for processings.
   *
   * This entity must exist in the database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritSingleExistingEntityInterface
   *   Our wrapper around a Drupal entity.
   */
  public function wrapExisting(EntityInterface $entity) : EntityInheritSingleExistingEntityInterface {
    return $this->getEntityFactory()->fromExistingEntity($entity);
  }

}
