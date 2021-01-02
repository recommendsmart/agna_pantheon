<?php

/**
 * @file
 * Openfarm Holding module tokens support.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function openfarm_holding_token_info() {
  $openfarm = [
    'name' => t('Openfarm'),
    'description' => t('Openfarm token types.'),
  ];

  $count_of_records['count-of-records'] = [
    'name' => t('Openfarm - count of records'),
    'description' => t('Count of record for specific holding'),
    'type' => 'openfarm',
  ];

  return [
    'types' => [
      'openfarm' => $openfarm,
    ],
    'tokens' => [
      'openfarm' => $count_of_records,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function openfarm_holding_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'openfarm' && !empty($data['holding_id'])) {
    $holding_id = $data['holding_id']->jsonSerialize();
    $entity_type_manager = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\Entity\Node $holding */
    $holding = $entity_type_manager->load((int) $holding_id);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'count-of-records':
          /** @var \Drupal\openfarm_holding\Service\OpenfarmHoldingServiceInterface $holding_service */
          $holding_service = \Drupal::service('openfarm_holding.holding_service');
          $replacements[$original] = $holding_service->getCountOfRecords($holding->id());
          $bubbleable_metadata->addCacheableDependency($holding);
          break;
      }
    }
  }

  return $replacements;
}
