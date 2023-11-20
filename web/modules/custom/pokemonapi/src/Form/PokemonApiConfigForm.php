<?php

namespace Drupal\pokemonapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PokemonApiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pokemonapi_main_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pokemonapi.main_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pokemonapi.main_settings');

    $form['pokeapitwo_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url - PokeAPI V2'),
      '#default_value' => $config->get('pokeapitwo_url'),
    ];

    $form['pokeapitwo_gridlimit'] = [
      '#type' => 'number',
      '#title' => $this->t('Grid Limit on Main Page'),
      '#default_value' => $config->get('pokeapitwo_gridlimit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('pokemonapi.main_settings')
      ->set('pokeapitwo_url', $form_state->getValue('pokeapitwo_url'))
      ->set('pokeapitwo_gridlimit', $form_state->getValue('pokeapitwo_gridlimit'))
      ->save();
  }

}
