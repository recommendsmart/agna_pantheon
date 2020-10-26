<?php

namespace Drupal\dcp_demo\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class RocketshipCookiePolicySubscriber.
 */
class DCPDemoSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * RocketshipCookiePolicySubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE] = ['onMigratePostRowSaveEvent'];

    return $events;
  }

  /**
   * Event handler callback.
   *
   * This does assume there's only one node being migrated. If there are
   * multiple then the last one will stick, as this is executed for every row.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The event to work with.
   */
  public function onMigratePostRowSaveEvent(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration();
    if ($migration->id() === 'dcp_page') {
      $nid = reset($event->getDestinationIdValues());
      $node = Node::load($nid);
      $uuid = $node->uuid();

      switch ($uuid) {
        case 'f6c9d484-8c89-4123-a95f-bc43f5f95a38':
          // Update cookie policy settings, set new page and company name.
          $vals = $event->getDestinationIdValues();
          $nid = reset($vals);
          $config = $this->configFactory->getEditable('rocketship_cookie_policy.cookiepolicyconfig');
          $config->set('more_info_page', $nid);
          $config->set('company_name', $this->configFactory->get('system.site')
            ->get('name'));
          $config->save();
          break;

        case 'f500d2f6-2b9e-4927-956c-d64bef92fc8a':
          // Update cookie policy settings with privacy policy info.
          $vals = $event->getDestinationIdValues();
          $nid = reset($vals);
          $config = $this->configFactory->getEditable('rocketship_cookie_policy.cookiepolicyconfig');
          $config->set('privacy_policy', $nid);
          $config->save();
          break;

        case '3a4e6368-ebee-413c-a06c-89cd94569c2e':
          // Update cookie policy settings with disclaimer info.
          $vals = $event->getDestinationIdValues();
          $nid = reset($vals);
          $config = $this->configFactory->getEditable('rocketship_cookie_policy.cookiepolicyconfig');
          $config->set('legal_disclaimer', $nid);
          $config->save();
          break;
      }
    }
  }
}
