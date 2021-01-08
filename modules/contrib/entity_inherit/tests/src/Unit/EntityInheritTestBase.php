<?php

namespace Drupal\Tests\entity_inherit\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\entity_inherit\EntityInheritEvent\EntityInheritEventBase;
use Drupal\entity_inherit\EntityInheritEvent\EntityInheritEventInterface;

/**
 * Base class for testing.
 */
class EntityInheritTestBase extends TestCase {

  /**
   * Get dummy (mock) event.
   *
   * @return \Drupal\entity_inherit\EntityInheritEvent\EntityInheritEventInterface
   *   A dummy (mock) event.
   */
  public function mockEvent() : EntityInheritEventInterface {
    return new EntityInheritEventBase([], 0);
  }

}
