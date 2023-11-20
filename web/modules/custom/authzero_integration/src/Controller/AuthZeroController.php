<?php

namespace Drupal\authzero_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\authzero_integration\AuthZeroService;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Handler for Auth0 login/logout callbacks.
 */
class AuthZeroController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface $account
   */
  protected $account;

  /**
   * Instance of Drupal\authzero_integration\AuthZeroService.
   *
   * @var \Drupal\authzero_integration\AuthZeroService
   */
  protected $authZeroService;

  /**
   * Constructs a ToolbarController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\authzero_integration\AuthZeroService $authZeroService
   *   Instance of AuthZeroService.
   */
  public function __construct(
    AccountInterface $account,
    AuthZeroService $authZeroService,
    ) {
    $this->account = $account;
    $this->authZeroService = $authZeroService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('authzero_integration.main_service')
    );
  }

  /**
   * Handles redirecting to auth0 login page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @throws \Auth0\SDK\Exception\CoreException
   */
  public function login(Request $request) {
    // Check if the current logged-in user is not anonymous.
    if ($this->account->isAnonymous()) {
      $postLoginLink = $this->authZeroService->getCallBackUrl();
      $auth0 = $this->authZeroService->getInstance();

      return new TrustedRedirectResponse($auth0->login($postLoginLink));
    }
    else {
      $homeUrl = Url::fromRoute('pokemonapi.user.home')->toString();
      return new RedirectResponse($homeUrl);
    }
  }

  /**
   * Call back function, invoked when user is authenticated by Auth0.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   The location to redirect after login.
   *
   * @throws \Auth0\SDK\Exception\ApiException
   *   Any misconfiguration will throw the Auth0 Exception.
   * @throws \Auth0\SDK\Exception\CoreException
   *   Any misconfiguration will throw the Auth0 Exception.
   */
  public function auth0Callback(Request $request): RedirectResponse {

    $frontUrl = Url::fromRoute('<front>')->toString();

    if ($this->account->isAnonymous()) {

      try {
        $auth0 = $this->authZeroService->getInstance();

        if (null !== $auth0->getExchangeParameters()) {
          $postLoginLink = $this->authZeroService->getCallBackUrl();

          $auth0->exchange($postLoginLink);

          $user_credentias = $auth0->getCredentials()?->user;

          if (isset($user_credentias['email']) && !empty($user_credentias['email'])) {

            $loadedUser = user_load_by_mail($user_credentias['email']);

            if (!empty($loadedUser)) {
              user_login_finalize($loadedUser);
              \Drupal::logger('authzero_integration')->info('Successfully logged in ' . $loadedUser->getEmail());
              return new RedirectResponse($this->authZeroService->getPostLoginRedirectLink());
            } else {
              $newUser = User::create();
              $newUser->setPassword($user_credentias['email'] . '.' . time());
              $newUser->enforceIsNew();
              $newUser->setEmail($user_credentias['email']);
              $newUser->setUsername($user_credentias['email']);
              $newUser->activate();
              //Save user account
              $newUser->save();

              $loadedUser = user_load_by_mail($user_credentias['email']);
              user_login_finalize($loadedUser);

              \Drupal::logger('authzero_integration')->info('Successfully created and logged in ' . $newUser->getEmail());
              return new RedirectResponse($this->authZeroService->getPostLoginRedirectLink());
            }
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger(__FUNCTION__)->error($e->getMessage());
      }
    }

    return new RedirectResponse($frontUrl);
  }

  /**
   * Handles user logout, from Drupal as well as Auth0.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Logout user from drupal and redirect to auth0 logout.
   */
  public function logout(): RedirectResponse {

    if (!empty($this->account->getEmail())) {
      $frontUrl = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
      $auth0 = $this->authZeroService->getInstance();
      // Logout from drupal plattform.
      user_logout();
      // Logout from autho0.
      //$logoutUrl = $auth0->logout($frontUrl->toString());
      return new RedirectResponse($frontUrl->toString());
    }
    else {
      return new RedirectResponse($this->authZeroService->getPostLoginRedirectLink());
    }
  }

  /**
   * Force user to logout.
   *
   * @return Symfony\Component\HttpFoundation\TrustedRedirectResponse
   *   Redirect to Auth0 logout link.
   */
  public function logoutUser($error = NULL): TrustedRedirectResponse {
    return new TrustedRedirectResponse($this->authZeroService->getLogoutLink($error));
  }

}
