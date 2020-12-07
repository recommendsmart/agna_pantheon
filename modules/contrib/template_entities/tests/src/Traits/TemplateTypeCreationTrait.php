<?php

namespace Drupal\Tests\template_entities\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\template_entities\Entity\TemplateType;
use PHPUnit\Framework\TestCase;

/**
 * Provides methods to create template type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait TemplateTypeCreationTrait {

  /**
   * Creates a template type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\template_entities\Entity\TemplateType
   *   Created template type.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTemplateType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['id'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (TemplateType::load($id));
    }
    else {
      $id = $values['id'];
    }

    $values += [
      'id' => $id,
      'type' => 'canonical_entities:node',
      'label' => 'Node template type',
      'description' => 'Test node template type.',
      'bundles' => ['page'],
    ];

    $type = TemplateType::create($values);
    $status = $type->save();

    if ($this instanceof TestCase) {
      $this->assertSame($status, SAVED_NEW, (new FormattableMarkup('Created template type %type.', ['%type' => $type->id()]))->__toString());
    }
    else {
      $this->assertEqual($status, SAVED_NEW, (new FormattableMarkup('Created template type %type.', ['%type' => $type->id()]))->__toString());
    }

    return $type;
  }

}
