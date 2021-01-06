<?php

namespace Drupal\openfarm_statistics\EventSubscriber;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OpenfarmRecordEventSubscriber.
 */
class OpenfarmStatisticsEventSubscriber implements EventSubscriberInterface {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Make sure to react on event before layout_builder.
    // @see BlockComponentRenderArray
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender', 101];
    return $events;
  }

  /**
   * Adds to the statistics and status block context current node.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   Event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $context = $event->getContexts();
    $plugin = $event->getPlugin();
    if (($plugin->getPLuginId() == 'openfarm_statistics_record_statistics'
        || $plugin->getPluginId() == 'openfarm_statistics_and_status'
        || $plugin->getPluginId() == 'openfarm_statistics_holding_statistics')
      && isset($context['entity'])) {
      $plugin->setContext('view_mode', $context['view_mode']);
    }
  }

}
