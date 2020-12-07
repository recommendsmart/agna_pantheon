<?php

namespace Drupal\Tests\template_entities\Unit\Menu;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\template_entities\TemplateManagerInterface;
use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests action local tasks.
 *
 * @group action
 */
class TemplateEntitiesLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * Tests local task existence.
   */
  public function testTemplateEntitiesLocalTasks() {
    // Templates tab on canonical route for entity_type1.
    $this->assertLocalTasks('entity.entity_type1.templates', [
      0 => ['entity_templates_ui:entity.entity_type1.templates'],
    ]);

    // No templates tab for entity_type2.
    $this->assertLocalTasks('entity.entity_type2.templates', []);
  }

  protected function setUp(): void {
    $this->directoryList = ['template_entities' => 'modules/contrib/template_entities'];

    parent::setUp();

    $entity_type1 = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type1->method('hasLinkTemplate')
      ->with('canonical')
      ->willReturn(TRUE);
    $entity_type1->method('hasViewBuilderClass')
      ->willReturn(TRUE);
    $entity_type1->method('id')
      ->willReturn('entity_type1');

    $entity_type2 = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type2->method('id')
      ->willReturn('entity_type2');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinitions')
      ->willReturn([
        'entity_type1' => $entity_type1,
        'entity_type2' => $entity_type2,
      ]);

    $template_manager = $this->createMock(TemplateManagerInterface::class);
    $template_manager->method('getTemplateTypesForEntityType')
      ->willReturn([
        'entity_type1' => ['template_type1'],
      ]);

    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('template_entities.manager', $template_manager);
    $this->container->set('string_translation', $this->getStringTranslationStub());
  }

}
