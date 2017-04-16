<?php

namespace Drupal\google_oauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_oauth\GoogleOAuth;

class GoogleOAuthConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'google_oauth_admin_settings';
  }

  public function getEditableConfigNames() {
    return [
      'google_oauth.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_oauth.settings');

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_oauth.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();
  }

}
