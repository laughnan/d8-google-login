<?php

namespace Drupal\google_oauth;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;

class GoogleOAuthClient extends \Google_Client {

  public function __construct(
    ConfigFactoryInterface $configFactory,
    UrlGeneratorInterface $urlGenerator
  ) {
    parent::__construct();

    $config = $configFactory->get('google_oauth.settings');

    $this->setClientId($config->get('client_id'));
    $this->setClientSecret($config->get('client_secret'));
    $this->setScopes(['email']);
    $this->setState('offline');

    $uri = $urlGenerator->generateFromRoute(
      'google_oauth.authenticate', [], ['absolute' => TRUE]
    );

    $this->setRedirectUri($uri);
  }

}
