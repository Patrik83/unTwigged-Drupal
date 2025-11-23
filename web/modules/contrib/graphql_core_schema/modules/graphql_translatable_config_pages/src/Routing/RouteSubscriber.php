<?php

namespace Drupal\graphql_translatable_config_pages\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.translatable_config_pages.collection')) {
      // Administer permission should not be required to access the collection.
      // The 'administer translatable config pages types' permission gives access to
      // manage types also, which we don't want to give to clients as they
      // can break deployments.
      $route->setRequirement('_permission', 'manage translatable config pages');
    }
  }

}
