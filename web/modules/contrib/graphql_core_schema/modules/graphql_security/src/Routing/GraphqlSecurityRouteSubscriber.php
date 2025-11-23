<?php

declare(strict_types=1);

namespace Drupal\graphql_security\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class GraphqlSecurityRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {

    $storage = \Drupal::entityTypeManager()->getStorage('graphql_server');
    /** @var \Drupal\graphql\Entity\ServerInterface[] $servers */
    $servers = $storage->loadMultiple();

    foreach ($servers as $id => $server) {
      if ($server->schema === 'core_composable') {
        if ($route = $collection->get("graphql.query.{$id}")) {
          $route->setRequirements([
            '_graphql_security_query_access' => 'TRUE',
            '_format' => 'json',
          ]);
        }
      }
    }
  }

}
