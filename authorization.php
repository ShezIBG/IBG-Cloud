<?php 

  ini_set('display_errors', 'On');
  require __DIR__ . '/vendor/autoload.php';

  
  session_start();

  $provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => 'AA15D8E97B0245EE8F4A3DFC741E880E',   
    'clientSecret'            => 'M5ja79KoTKniEinZxXzkOJpmwRlgHH7FW7TR0zyru2YVh-2e',
    'redirectUri'             => 'https://ibg-uk.cloud',
    'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
    'urlAccessToken'          => 'https://identity.xero.com/connect/token',
    'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation',
  ]);

  // Scope defines the data your app has permission to access.
  // Learn more about scopes at https://developer.xero.com/documentation/oauth2/scopes
  $options = [
    'scope' => ['openid profile email accounting.transactions offline_access']
  ];

  // This returns the authorizeUrl with necessary parameters applied (e.g. state).
  $authorizationUrl = $provider->getAuthorizationUrl($options);

  // Save the state generated for you and store it to the session.
  // For security, on callback we compare the saved state with the one returned to ensure they match.
  $_SESSION['oauth2state'] = $provider->getState();

  // Redirect the user to the authorization URL.
  header("Access-Control-Allow-Origin: https://ibg-uk.cloud");
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header("Access-Control-Allow-Headers: Authorization");

  $postdata = file_get_contents("php://input");
  $request = json_decode($postdata);
  print_r($request);
  header('Location: ' . $authorizationUrl);
  exit();
?>