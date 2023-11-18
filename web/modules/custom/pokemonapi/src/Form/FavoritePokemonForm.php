<?php

namespace Drupal\pokemonapi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\user\Entity\User;
use Drupal\Core\Link;

/**
 *
 */
class FavoritePokemonForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pokemonapi_favorite_pokemon_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pokemons = []) {

    foreach ($pokemons as $pokemon_info) {
      $pokemonId = $pokemon_info['id'];
      $form["favorite_button_{$pokemonId}"] = [
        '#type' => 'button',
        '#value' => $this->t("Save pokemon {$pokemonId} as favorite."),
        '#ajax' => [
          'callback' => '::saveAsFavoriteButtonCallback',
          'event' => 'click',
          'progress' => false,
        ],
        '#attributes' => [
          'pokemon-id' => $pokemonId,
          'hidden' => true,
        ],
      ];
    }

    return $form;
  }

  /**
   *
   */
  public function saveAsFavoriteButtonCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    try {
      $current_user = \Drupal::currentUser();

      if ($current_user) {
        $pokemonId = $form_state->getTriggeringElement()['#attributes']['pokemon-id'];
        // Load current user.
        $user = User::load($current_user->id());
        // Check favorites.
        $favorites = $user->get('field_favorite_pokemon_ids')->getValue();

        if ($favorites) {

          $alreadyLiked = false;
          $newLikedArray = [];
          // Check if liked pokemon is already saved.
          foreach ($favorites as $favorite) {

            if ($favorite['value'] == $pokemonId) {
              $alreadyLiked = true;
            }
            else {
              $newLikedArray[] = $favorite;
            }
          }

          if (!$alreadyLiked) {

            if ((count($favorites) + 1) > 10) {
              $response->addCommand(new InvokeCommand(null, 'showFavoritesLimitMessage'));
              return $response;
            }
            // Add new pokemon id.
            $favorites[] = ['value' => $pokemonId];
            $user->set('field_favorite_pokemon_ids', $favorites);
          }
          else {
            // Remove pokemon id.
            $user->set('field_favorite_pokemon_ids', $newLikedArray);
          }
        }
        else {
          // Set new favorite pokemon.
          $user->set('field_favorite_pokemon_ids', [
            ['value' => $pokemonId],
          ]);
        }

        $user->save();
        $response->addCommand(new InvokeCommand(null, 'markIconAsFavorite', [$pokemonId]));
      }
    } catch (\Throwable $th) {
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
