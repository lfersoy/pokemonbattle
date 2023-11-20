<?php

namespace Drupal\pokemonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;

/**
 *
 */
class RoutingController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface $account
   */
  protected $account;

  /**
   * The pokemonApi Settings.
   *
   * @var array
   */
  protected $pokemonApiConfig;

  /**
   * The pokemonApi url.
   *
   * @var array
   */
  protected $pokemonApiUrl;

  /**
   * The pokemonApi grid limit.
   *
   * @var array
   */
  protected $pokemonApiGridLimit;

  /**
   * Constructs a ToolbarController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $pokemonApiConfig
   *   The config factory object.
   */
  public function __construct(
    AccountInterface $account,
    ConfigFactoryInterface $pokemonApiConfig) {
    $this->account = $account;
    $this->pokemonApiConfig = $pokemonApiConfig->get('pokemonapi.main_settings');
    $this->pokemonApiUrl = $this->pokemonApiConfig->get('pokeapitwo_url');
    $this->pokemonApiGridLimit = $this->pokemonApiConfig->get('pokeapitwo_gridlimit');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   *
   */
  public function favoritesPage() {

    if (!$this->account->isAnonymous()) {
      $user = User::load($this->account->id());
      $favorites = $user->get('field_favorite_pokemon_ids')->getValue();
      $build = $pokemons = $promises = [];
      // Create a Guzzle Client instance
      $client = new Client();

      // Process favorites.
      foreach ($favorites as $favorite) {
        $request = new Request('GET', "{$this->pokemonApiUrl}pokemon/{$favorite['value']}");
        $promises[] = $client->sendAsync($request);
      }

      $responses = Promise\settle($promises)->wait();

      foreach ($responses as $promise) {

        if ($promise['state'] === 'fulfilled') {
          $response = $promise['value'];
          $body = $response->getBody()->getContents();
          $data = json_decode($body, true);

          $pokemons[$data['id']] = [
            'id' => $data['id'],
            'name' => $data['name'],
            'image_url' => isset($data['sprites']['front_shiny']) ?
              $data['sprites']['front_shiny'] : '',
          ];
        }
      }

      // Build page.
      $build['page'] = [
        '#theme'  => 'pokemonbattle_favoritespage',
        '#pokemons' => $pokemons,
        '#pokemon_form' => null,
      ];

      return $build;
    }
    else {
      $frontUrl = Url::fromRoute('<front>')->toString();
      return new RedirectResponse($frontUrl);
    }
  }

  /**
   *
   */
  public function homePage() {

    if (!$this->account->isAnonymous()) {
      $user = User::load($this->account->id());
      $favorites = $user->get('field_favorite_pokemon_ids')->getValue();
      $build = $pokemons = $user_favorites = [];

      // Process favorites.
      foreach ($favorites as $favorite) {
        $user_favorites[] = $favorite['value'];
      }

      // Get pokemons info from pokeapi v2.
      $client = \Drupal::httpClient();
      $request = $client->request('GET', "{$this->pokemonApiUrl}pokemon?limit={$this->pokemonApiGridLimit}", []);
      $repsonse = json_decode($request->getBody(), TRUE);

      foreach ($repsonse['results'] as $pokemon) {

        if (isset($pokemon['url'])) {
          $request = $client->request('GET', $pokemon['url'], []);
          $pokemon_info = json_decode($request->getBody(), TRUE);
          $image_url = isset($pokemon_info['sprites']['front_shiny']) ?
            $pokemon_info['sprites']['front_shiny'] : '';

          $pokemons[] = [
            'id' => $pokemon_info['id'],
            'name' => $pokemon['name'],
            'image_url' => $image_url,
            'liked' => in_array($pokemon_info['id'], $user_favorites) ?
              true : false,
          ];
        }
      }

      $form = \Drupal::formBuilder()
        ->getForm('Drupal\pokemonapi\Form\FavoritePokemonForm', $pokemons);

      // Build page.
      $build['page'] = [
        '#theme'  => 'pokemonbattle_homepage',
        '#pokemons' => $pokemons,
        '#pokemon_form' => $form,
      ];

      return $build;
    }
    else {
      $frontUrl = Url::fromRoute('<front>')->toString();
      return new RedirectResponse($frontUrl);
    }
  }

  /**
   *
   */
  public function landingPage() {

    if (!$this->account->isAnonymous()) {
      $frontUrl = Url::fromRoute('pokemonapi.user.home')->toString();
      return new RedirectResponse($frontUrl);
    }

    // Build page.
    $build['page'] = [
      '#theme'  => 'pokemonbattle_landingpage',
    ];

    return $build;
  }

  /**
   *
   */
  public function battleModal(HttpRequest $httpRequest) {

    $postData = json_decode($httpRequest->getContent(), 1);

    // Check user.
    if ($this->account->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    // Check post data.
    if (!isset($postData['pokemon_ids']) ||
      empty($postData['pokemon_ids']) ||
      count($postData['pokemon_ids']) !== 2) {
      throw new BadRequestHttpException();
    }

    // Create a Guzzle Client instance.
    $client = new Client();
    $promises = $pokemons = [];

    // Process selected pokemon ids.
    foreach ($postData['pokemon_ids'] as $pokemonId) {
      $queryRequest = new Request('GET', "{$this->pokemonApiUrl}pokemon/{$pokemonId}");
      $promises[] = $client->sendAsync($queryRequest);
    }

    $responses = Promise\settle($promises)->wait();

    foreach ($responses as $key => $promise) {

      if ($promise['state'] === 'fulfilled') {
        $response = $promise['value'];
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $pokemonId = $data['id'];
        $stats = [];
        $totalPoints = 0;

        if (isset($data['stats'])) {
          foreach ($data['stats'] as $stat) {
            $statName = $stat['stat']['name'];
            $stats[$statName] = [
              'name' => $statName,
              'value' => $stat['base_stat'],
              'winner' => true,
            ];

            // Compare with previous pokemon.
            if (isset($pokemons[$key-1]['stats'][$statName])) {
              $previousStat = $pokemons[$key-1]['stats'][$statName]['value'];
              if ($previousStat > $stat['base_stat']) {
                $stats[$statName]['winner'] = false;
              }
              else {
                $pokemons[$key-1]['stats'][$statName]['winner'] = false;
              }
            }

            $totalPoints += $stat['base_stat'];
          }
        }

        $pokemons[$key] = [
          'id' => $pokemonId,
          'name' => $data['name'],
          'image_url' => isset($data['sprites']['front_shiny']) ?
            $data['sprites']['front_shiny'] : '',
          'stats' => $stats,
          'score' => $totalPoints,
        ];
      }
    }

    $winner = $pokemons[1]['name'];

    if ($pokemons[0]['score'] > $pokemons[1]['score']) {
      $winner = $pokemons[0]['name'];
    }

    $render_array = [
      '#theme' => 'pokemonbattle_battlemodale',
      '#pokemons' => $pokemons,
      '#winner' => $winner,
    ];

    $output = \Drupal::service('renderer')->renderRoot($render_array);

    return new JsonResponse(['output' => $output]);
  }
}
