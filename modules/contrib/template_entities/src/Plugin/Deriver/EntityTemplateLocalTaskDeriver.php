<?php

namespace Drupal\template_entities\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\template_entities\TemplateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for the template entities user interface.
 *
 * @internal
 */
class EntityTemplateLocalTaskDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The template manager.
   *
   * @var \Drupal\template_entities\TemplateManagerInterface
   */
  protected $templateManager;

  /**
   * Constructs a new LayoutBuilderLocalTaskDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\template_entities\TemplateManagerInterface $template_manager
   *   The template manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TemplateManagerInterface $template_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->templateManager = $template_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('template_entities.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Add a "Templates" tab to entities of types that are used as a templates
    // by template types.
    foreach ($this->getEntityTypesForTemplates() as $entity_type_id => $entity_type) {
      // Overrides.
      $this->derivatives["entity.$entity_type_id.templates"] = $base_plugin_definition + [
          'route_name' => "entity.$entity_type_id.templates",
          'weight' => 15,
          'title' => $this->t('Templates'),
          'base_route' => "entity.$entity_type_id.canonical",
        ];
    }

    return $this->derivatives;
  }

  /**
   * Returns an array of entity types relevant for overrides.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types.
   */
  protected function getEntityTypesForTemplates() {
    $templateEnabledEntityTypes = $this->templateManager->getTemplateTypesForEntityType();
    return array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entity_type) use ($templateEnabledEntityTypes) {
      return isset($templateEnabledEntityTypes[$entity_type->id()]) && $entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical');
    });
  }

}
