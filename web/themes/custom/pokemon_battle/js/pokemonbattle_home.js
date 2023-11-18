/**
 * @file
 * Landing page.
 *
 */

(function ($, Drupal) {
  /**
   * .
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for drupalAnnounce.
   */
  Drupal.behaviors.pokemonbattle_main = {
    attach(context) {

      $(".pokemon-grid").one("click", ".like-button", function (e) {
        e.preventDefault();

        const pokemon_id = $(this).attr("pokemon-id");
        $(`.poke-form input[pokemon-id='${pokemon_id}']`).click();
      });
    },
  };

  $.fn.showFavoritesLimitMessage = function () {
    alert("The limit of favorite pokemon is 10!");
  };

  $.fn.markIconAsFavorite = function (pokemon_id) {

    $cardElement = $(`.pokemon-grid button[pokemon-id="${pokemon_id}"]`);

    $cardElement
      .find("path[icon-type='unliked']")
      .attr("hidden", function (_, attr) {
        return attr ? null : "hidden";
      });

    $cardElement
      .find("path[icon-type='liked']")
      .attr("hidden", function (_, attr) {
        return attr ? null : "hidden";
      });
  };

  $(document).ready(function () {});
})(jQuery, Drupal);
