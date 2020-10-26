<?php

# Src: https://www.chapterthree.com/blog/how-alter-entity-autocomplete-results-drupal-8

namespace Drupal\rocketship_paragraphs\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\rocketship_paragraphs\Matcher\EntityAutocompleteMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityAutocompleteController extends \Drupal\system\Controller\EntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('rocketship_paragraphs.autocomplete_matcher'),
        $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
