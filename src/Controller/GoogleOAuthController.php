<?php
/**
 * Our first Drupal 8 controller.
 */
namespace Drupal\google_oauth\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class GoogleOAuthController extends ControllerBase implements ContainerInjectionInterface {

  private $client;

  private $config;

  private $externalAuth;

  private $moduleHandler;

  private $requestStack;

  public function __construct(
    \Google_Client $client,
    ConfigFactoryInterface $configFactory,
    ExternalAuthInterface $externalAuth,
    LoggerChannelInterface $logger,
    ModuleHandlerInterface $moduleHandler,
    RequestStackInterface $requestStack
  ) {
    $this->client = $client;
    $this->config = $configFactory->get('google_oauth.settings');
    $this->externalAuth = $externalAuth;
    $this->logger = $logger;
    $this->moduleHandler = $moduleHandler;
    $this->requestStack = $requestStack;
  }

  public static function create(ContainerInterface $container) {
    new static(
      $container->get('google_oauth.client'),
      $container->get('config.factory'),
      $container->get('externalauth.externalauth'),
      $container->get('logger.channel.externalauth'),
      $container->get('module_handler'),
      $container->get('request_stack')
    );
  }

  public function login() {
    if (!$this->client) {
      return false;
    }

    return new TrustedRedirectResponse($this->client->createAuthUrl(), 301);
  }

  public function authenticate() {
    if (!$this->client) {
      $this->logger->log(
        LogLevel::ERROR,
        'There was an error loading the Google OAuth client.',
        $request->query->all()
      );

      return new RedirectResponse($this->config->get('page_error_location'));
    }

    $request = $this->requestStack->getMasterRequest();
    $code = $request->query->get('code');

    if (!$request->query->has('code')) {
      $this->logger->log(
        LogLevel::ERROR,
        'The code parameter was empty.',
        $request->query->all()
      );

      return new RedirectResponse($this->config->get('page_error_location'));
    }

    try {
      $this->client->authenticate($code);
    }
    catch (\Exception $e) {
      $this->logger->log(LogLevel::ERROR, $e->getMessage());

      return new RedirectResponse($this->config->get('page_error_location'));
    }

    $oauth = new \Google_Service_Oauth2($this->client);
    $userData = $oauth->userinfo->get();

    // @todo add event handler
    $this->moduleHandler->alter(
      'google_oauth_user_data',
      $userData
    );

    // Pass the account information received to the ExternalAuth service to
    // either login or register the user.
    try {
      $account = $this->externalAuth->loginRegister(
        $userData['email'],
        'google_oauth',
        [
          'name' => $userData['name'],
          'status' => TRUE,
          'mail' => $userData['email'],
          'picture' => $userData['picture'],
        ]
      );
    }
    catch (\Exception $e) {
      $this->logger->log(LogLevel::ERROR, $e->getMessage());

      return new RedirectResponse($this->config->get('page_error_location'));
    }

    // @todo add event handler
    $this->moduleHandler->invokeAll(
      'google_oauth_user_login_register',
      $account,
      $userData
    );

    return new RedirectResponse($this->config->get('page_success_location'));
  }
}
