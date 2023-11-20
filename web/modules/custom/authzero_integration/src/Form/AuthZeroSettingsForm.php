<?php

namespace Drupal\authzero_integration\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AuthZero Settings Form class.
 */
class AuthZeroSettingsForm extends ConfigFormBase {

  /**
   * Instance of Drupal\Core\Routing\RouteProvider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->routeProvider = $container->get('router.route_provider');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'authzero_integration.main.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'authzero_integration_main_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('authzero_integration.main.settings');

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#description' => $this->t('Domain added in the Application page on auth0 platform.'),
      '#default_value' => $config->get('domain'),
      '#required' => TRUE,
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Id'),
      '#description' => $this->t('Client Id associated with the Application.'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('Client Secret associated with the Application.'),
      '#default_value' => $config->get('client_secret'),
      '#required' => TRUE,
    ];

    $form['cookie_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Secret'),
      '#description' => $this->t('Generate a sufficiently long, random string.'),
      '#default_value' => $config->get('cookie_secret'),
      '#required' => TRUE,
    ];

    $form['redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#description' => $this->t('The URI to redirect, after successfully authenticated by auth0 platform.'),
      '#default_value' => $config->get('redirect_uri') ?? \Drupal::request()->getSchemeAndHttpHost() . '/auth0/callback',
      '#required' => TRUE,
    ];

    $form['post_login_route'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route name'),
      '#description' => $this->t('Name of the route to redirect after login.'),
      '#default_value' => $config->get('post_login_route') ?? '<front>',
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate if route exists.
    $route_name = $form_state->getValue('post_login_route');
    $route_exists = $this->routeProvider->getRoutesByNames([$route_name]);
    if (empty($route_exists)) {
      $form_state->setErrorByName('post_login_route', $this->t('Route name does not exist.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = Url::fromRoute($form_state->getValue('post_login_route'), [], ['absolute' => TRUE]);
    parent::submitForm($form, $form_state);
    $this->config('authzero_integration.main.settings')
      ->set('domain', $form_state->getValue('domain'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('cookie_secret', $form_state->getValue('cookie_secret'))
      ->set('callback_url', $form_state->getValue('redirect_uri'))
      ->set('post_login_route', $form_state->getValue('post_login_route'))
      ->set('post_login_url', $url->toString())
      ->save();
  }

}
