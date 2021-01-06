<?php

/**
 * @file
 * Openfarm Holding module tokens support.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function openfarm_statistics_token_info() {
  $types = [
    'name' => t('Openfarm'),
    'description' => t('Openfarm token types.'),
  ];
  $tokens = [
    'records-count' => [
      'name' => t('Openfarm - the count of all records'),
      'description' => t('The count of all records in the site'),
      'type' => 'openfarm',
    ],
    'comments-count' => [
      'name' => t('Openfarm - the count of all comments'),
      'description' => t('The count of all comments in the site'),
      'type' => 'openfarm',
    ],
    'members-count' => [
      'name' => t('Openfarm - the count of al members'),
      'description' => t('The count of all member is the site'),
      'type' => 'openfarm',
    ],
    'votes-count' => [
      'name' => t('Openfarm - the count of all votes'),
      'description' => t('The count of all votes in the site'),
      'type' => 'openfarm',
    ],
    'node-votes-count' => [
      'name' => t('Record votes count'),
      'type' => 'openfarm',
    ],
    'holding-records-count' => [
      'name' => t('Holding records count'),
      'type' => 'openfarm',
    ],
    'user-votes-count' => [
      'name' => t('User votes count'),
      'type' => 'openfarm',
    ],
    'user-comments-count' => [
      'name' => t('User comments count'),
      'type' => 'openfarm',
    ],
    'user-records-count' => [
      'name' => t('User records count'),
      'type' => 'openfarm',
    ],

  ];

  return [
    'types' => [
      'openfarm' => $types,
    ],
    'tokens' => [
      'openfarm' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function openfarm_statistics_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  // Add tokens that return total count of: users, comments, votes.
  if ($type == 'openfarm') {
    $entity_type_manager = \Drupal::entityTypeManager();
    foreach ($tokens as $name => $original) {

      switch ($name) {
        case 'members-count':
          $query = $entity_type_manager->getStorage('user')->getQuery();
          $result = $query
            ->condition('status', '1')
            // @Todo: Remove once we will find better way to resolve this issue,
            // and remove the same logic from community page view.
            ->condition('uid', '1', '>')
            ->count()
            ->execute();
          $replacements[$original] = $result;
          $bubbleable_metadata->addCacheTags(['user_list']);
          break;

        case 'comments-count':
          $query = $entity_type_manager->getStorage('comment')->getQuery();
          $result = $query
            ->condition('status', '1')
            ->count()
            ->execute();
          $replacements[$original] = $result;
          $bubbleable_metadata->addCacheTags(['comment_list']);
          break;

        case 'records-count':
          $query = $entity_type_manager->getStorage('node')->getQuery();
          $result = $query
            ->condition('status', '1')
            ->condition('type', 'record')
            ->count()
            ->execute();
          $replacements[$original] = $result;
          $bubbleable_metadata->addCacheTags(['node_list:record']);
          break;

        case 'votes-count':
          $query = $entity_type_manager->getStorage('vote')->getQuery();
          $result = $query
            ->count()
            ->execute();
          $replacements[$original] = $result;
          $bubbleable_metadata->addCacheTags(['vote_list']);
          break;

        case 'node-votes-count':
          if (!empty($data['node'])) {
            $count = \Drupal::service('plugin.manager.votingapi.resultfunction')
              ->getResults('node', $data['node']->id());

            // If there are not votes on node, then the array is empty.
            $result = empty($count) ? 0 : $count['reaction_like']['vote_count'];

            $replacements[$original] = $result;
          }
          break;

        case 'user-votes-count':
          if (!empty($data['user'])) {
            $storage = \Drupal::entityTypeManager()->getStorage('vote');
            $count = $storage->getQuery()
              ->condition('user_id', $data['user']->id())
              ->condition('type', 'reaction_like')
              ->count()
              ->execute();

            $replacements[$original] = $count;
            $bubbleable_metadata->addCacheTags(Cache::mergeTags($data['user']->getCacheTags(), ['vote_list']));
          }
          break;

        case 'user-comments-count':
          if (!empty($data['user'])) {
            $query = $entity_type_manager->getStorage('comment')->getQuery();
            $result = $query
              ->condition('status', '1')
              ->condition('uid', $data['user']->id())
              ->count()
              ->execute();
            $replacements[$original] = $result;
            $bubbleable_metadata->addCacheTags(Cache::mergeTags($data['user']->getCacheTags(), ['comment_list']));
          }
          break;

        case 'user-records-count':
          if (!empty($data['user'])) {
            $result = \Drupal::entityQuery('node')
              ->condition('status', '1')
              ->condition('type', 'record')
              ->condition('uid', $data['user']->id())
              ->count()
              ->execute();
            $replacements[$original] = $result;
            $bubbleable_metadata->addCacheTags(Cache::mergeTags($data['user']->getCacheTags(), ['node_list:record']));
          }
          break;

        case 'holding-records-count':
          if (!empty($data['node_id'])) {
            $storage = \Drupal::entityTypeManager()->getStorage('node');
            $node_query = $storage->getQuery();
            $result = $node_query->condition('field_holding', $data['node_id'], '=')
              ->condition('type', 'record')
              ->condition('status', 1)
              ->count()
              ->execute();
            $replacements[$original] = $result;
          }
          break;
      }
    }
  }

  return $replacements;
}
