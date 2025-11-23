<?php

declare(strict_types=1);

namespace Drupal\graphql_security\Routing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Custom access check for accessing the /graphql route.
 *
 * Anonymous users don't have the "execute graphql requests" permission and
 * therefore are not allowed to make queries. This is to prevent anyone from
 * making any GraphQL requests, since they can become quite complex and
 * therefore a performance issue.
 *
 * Since the frontend doesn't directly make any GraphQL requests, we can allow
 * nuxt-graphql-middleware in the frontend to do requests. It performs
 * GraphQL requests server side and passes the response to the frontend. It's
 * like a proxy between Drupal and browser.
 * It sends the secret token in the request header and we check for the
 * presence of it in this access check.
 *
 * One would think we could create an "api" account and give it the permission
 * to make requests and then authenticate ourselves from the nuxt server side,
 * which would have the same results. But unfortunately that would result in
 * annoying Drupal cache behavior because then every anonymous user would
 * actually be logged in.
 *
 * The restricted access is just for performance reasons, since no sensitive
 * information is available via GraphQL anyway, as entity access checks are
 * still performed in the queries and anonymous users don't have permission to
 * access other users information for example.
 */
class GraphqlSecurityAccessCheck implements AccessInterface {

  /**
   * The header key to check to token.
   *
   * @var string
   */
  const HEADER = 'x-drupal-graphql-token';

  /**
   * Constructs a GraphqlAccessCheck instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected RequestStack $requestStack,
  ) {
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // First check if the user has the permission to execute requests.
    // Anonymous users don't have this permission.
    if ($account->hasPermission('execute graphql_compose_server arbitrary graphql requests')) {
      return AccessResult::allowed();
    }

    $request = $this->requestStack->getCurrentRequest();

    $token = Settings::get('access_graphql.token');
    $tokenHeader = $request->headers->get(self::HEADER);

    // Check if the request is coming from nuxt-graphql-middleware.
    if ($token && $tokenHeader && hash_equals($token, $tokenHeader)) {
      return AccessResult::allowed();
    }

    // Denied.
    return AccessResult::forbidden();
  }

}
