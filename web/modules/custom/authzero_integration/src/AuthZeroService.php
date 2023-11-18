<?php

namespace Drupal\authzero_integration;

use Drupal\Core\Config\ConfigFactoryInterface;

use Auth0\SDK\Auth0;

/**
 * Set of utility functions.
 */
class AuthZeroService {

  /**
   * The authZero Settings.
   *
   * @var array
   */
  protected $authZeroConfig;

  /**
   * Defining constructor AuthZeroService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->authZeroConfig = $config_factory->get('authzero_integration.main.settings');
  }

  /**
   * Returns the Auth0 instance.
   *
   * @throws \Auth0\SDK\Exception\CoreException
   *   The Auth0 exception.
   */
  public function getInstance(): Auth0 {
    return new Auth0([
      'domain' => $this->authZeroConfig->get('domain'),
      'clientId' => $this->authZeroConfig->get('client_id'),
      'clientSecret' => $this->authZeroConfig->get('client_secret'),
      'cookieSecret' => $this->authZeroConfig->get('cookie_secret'),
    ]);
  }

  /**
   * User logout Link, for auth0.
   *
   * @param string|null $error
   *   The error messages.
   *
   * @return string
   *   The logout link.
   */
  public function getLogoutLink(string $error = NULL): string {
    return sprintf(
      'https://%s/v2/logout?client_id=%s&federated=true&returnTo=%s?error_description=%s',
      $this->authZeroConfig->get('domain'),
      $this->authZeroConfig->get('client_id'),
      \Drupal::request()->getSchemeAndHttpHost() . '/auth0/login',
      $error
    );
  }

  /**
   * Return the route to redirect to after login.
   *
   * @return string
   *   Redirect to the configured url after logging in.
   */
  public function getPostLoginRedirectLink(): string {
    return $this->authZeroConfig->get('post_login_url');
  }

  /**
   * Return the callback url.
   *
   * @return string
   *   An callack url
   */
  public function getCallBackUrl(): string {
    return $this->authZeroConfig->get('callback_url');
  }
}
