<?php

namespace Drupal\pokemonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
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
   * Constructs a ToolbarController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
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
        $request = new Request('GET', "https://pokeapi.co/api/v2/pokemon/{$favorite['value']}");
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
      $request = $client->request('GET', 'https://pokeapi.co/api/v2/pokemon?limit=16', []);
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
    // Build page.
    $build['page'] = [
      '#theme'  => 'pokemonbattle_landingpage',
    ];

    return $build;
  }
}
