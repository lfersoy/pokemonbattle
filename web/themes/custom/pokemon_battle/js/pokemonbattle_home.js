/**
 * @file
 * Landing page.
 *
 */

(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.pokemonbattle_main = {
    attach: function (context, settings) {
      const $elements = $(once("pokemonbattle_main", ".like-button", context));

      $elements.each(function () {
        $(this).click(function (event) {
          event.preventDefault();

          const pokemon_id = $(this).parent().attr("pokemon-id");
          $(`.poke-form input[pokemon-id='${pokemon_id}']`).click();
        });
      });
    },
  };

  Drupal.behaviors.pokemonbattle_modal = {
    attach: function (context, settings) {
      const $battleButtons = $(
        once("pokemonbattle_battle_buttons", ".battle-button", context)
      );
      const $modalInstance = $(
        once("pokemonbattle_battle_modal", "#pokemon-battle-modal", context)
      );

      $modalInstance.on("click", ".close-battle-modal", function (event) {
        event.preventDefault();
        $modalInstance.html("");
      });

      $battleButtons.each(function () {
        $(this).click(function (event) {
          event.preventDefault();

          const $parent = $(this).parent();
          const $swordIcon = $parent.parent().find("img[sword-incon='true']");
          const pokemon_id = $parent.attr("pokemon-id");

          let data = sessionStorage.getItem("selected_pokemon_ids");
          data = data ? JSON.parse(data) : [];

          if (data.includes(pokemon_id)) {
            $swordIcon.attr("hidden", "");
            // remove pokemon_id from data.
            const index = data.indexOf(pokemon_id);
            data.splice(index, 1);

            sessionStorage.setItem(
              "selected_pokemon_ids",
              JSON.stringify(data)
            );
            return;
          }

          data.push(pokemon_id);
          $swordIcon.removeAttr("hidden");
          sessionStorage.setItem("selected_pokemon_ids", JSON.stringify(data));

          if (data.length == 2) {
            sessionStorage.removeItem("selected_pokemon_ids");
            $.ajax({
              url: "/pokemonapi/battle",
              type: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              data: JSON.stringify({
                pokemon_ids: data,
              }),
              success: function (response) {
                $(".pokemon-grid")
                  .find("img[sword-incon='true']")
                  .attr("hidden", "");
                $modalInstance.html(response.output);
              },
            });
          }
        });
      });
    },
  };

  $.fn.showFavoritesLimitMessage = function () {
    alert("Limit of favorite pokemon is 10!");
  };

  $.fn.markIconAsFavorite = function (pokemon_id) {
    $cardElement = $(
      `.pokemon-grid div[pokemon-id="${pokemon_id}"] .like-button`
    );

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

  $(document).ready(function () {
    // Restore selected pokemon.
    sessionStorage.removeItem("selected_pokemon_ids");
  });
})(jQuery, Drupal, drupalSettings, once);
