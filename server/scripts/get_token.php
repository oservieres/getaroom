<?php

require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
));

$credentialsPath = $config['credentials_path'];

$client = new Google_Client();
$client->setApplicationName($config['application_name']);
$client->setScopes(SCOPES);
$client->setAuthConfig($config['client_secret_path']);
$client->setAccessType('offline');
$client->setRedirectUri("urn:ietf:wg:oauth:2.0:oob");

if (file_exists($credentialsPath)) {
    echo "Access token alreayd exists ! No need to generate it";
    exit;
}

// Request authorization from the user.
$authUrl = $client->createAuthUrl();
printf("Open the following link in your browser:\n%s\n", $authUrl);
print 'Enter verification code: ';
$authCode = trim(fgets(STDIN));

// Exchange authorization code for an access token.
$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

// Store the credentials to disk.
if(!file_exists(dirname($credentialsPath))) {
  mkdir(dirname($credentialsPath), 0700, true);
}
file_put_contents($credentialsPath, json_encode($accessToken));
printf("Credentials saved to %s\n", $credentialsPath);

// Refresh the token if it's expired.
if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
}

echo "Access token generated, you're good to go !";
