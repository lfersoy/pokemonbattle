<?php

namespace Drupal\authzero_integration\Routing;

use Drupal\authzero_integration\AuthZeroService;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Instance of Drupal\authzero_integration\AuthZeroService.
   *
   * @var \Drupal\authzero_integration\AuthZeroService
   */
  protected $authZeroService;

  /**
   * Defining constructor for Auth0.
   *
   * @param \Drupal\authzero_integration\AuthZeroService $authZeroService
   *   The AuthZero Service object.
   */
  public function __construct(AuthZeroService $authZeroService) {
    $this->authZeroService = $authZeroService;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    if ($collection->get('user.logout')) {
      $route = $collection->get('user.logout');

      if (is_object($route)) {
        $route->setDefaults(
          [
            '_controller' => '\Drupal\authzero_integration\Controller\AuthZeroController::logout',
          ]
        );
        $route->setOptions(
          [
            'no_cache' => 'TRUE',
          ]
        );
      }
    }
  }

}
