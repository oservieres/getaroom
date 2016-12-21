<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
));

if (!isset($_GET['calendar']) || !isset($config['calendars'][$_GET['calendar']])) {
    header('HTTP/1.1 400 BAD REQUEST');
    echo "Please provide a valid calendar name with 'calendar' parameter.";
    exit;
}
$calendarId = $config['calendars'][$_GET['calendar']];

$client = new Google_Client();
$client->setApplicationName($config['application_name']);
$client->setScopes(SCOPES);
$client->setAuthConfig($config['client_secret_path']);
$client->setAccessType('offline');
$client->setApprovalPrompt('force');
$client->setRedirectUri("urn:ietf:wg:oauth:2.0:oob");

// Get previously generated Access Token
$credentialsPath = $config['credentials_path'];
if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
    $client->setAccessToken($accessToken);
} else {
    header("HTTP/1.1 404 Not Found");
    echo "Error : Access Token not found. Please generate it.";
    exit;
}

// Refresh the token if it's expired.
if ($client->isAccessTokenExpired()) {
    $refreshToken = $client->getRefreshToken();
    $client->fetchAccessTokenWithRefreshToken($refreshToken);
    $content = $client->getAccessToken();
    $content['refresh_token'] = $refreshToken;
    file_put_contents($credentialsPath, json_encode($content));
}

$service = new Google_Service_Calendar($client);

$currentDate = new \DateTimeImmutable("today", new \DateTimeZone('UTC'));
$endDayDate = new \DateTimeImmutable("tomorrow", new \DateTimeZone('UTC'));
$currentTime = new \DateTimeImmutable(null, new \DateTimeZone('UTC'));
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => $currentDate->format("c"),
  'timeMax' => $endDayDate->format("c"),
  'timeZone' => 'Europe/Paris',
);
$results = $service->events->listEvents($calendarId, $optParams);

$data = [
    'current' => null,
    'next' => null,
];

if (count($results->getItems()) > 0) {
  foreach ($results->getItems() as $event) {
    $start = $event->start->dateTime;
    if (empty($start)) {
      $start = $event->start->date;
    }
    $start = new \DateTime($start);

    $end = $event->end->dateTime;
    if (empty($end)) {
      $end = $event->end->date;
    }
    $end = new \Datetime($end);

    $eventType = null;
    if ($start < $currentTime && $end > $currentTime) {
        $data['current'] = [
            'summary' => $event->getSummary(),
            'creator' => $event->getCreator()->getEmail(),
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
        ];
    } else if ($start > $currentTime) {
        $data['next'] = [
            'summary' => $event->getSummary(),
            'creator' => $event->getCreator()->getEmail(),
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
        ];
        break;
    }

  }
}

header("HTTP/1.1 200 OK");
header('Content-Type: Content-type: application/xml');
$xml = <<<XML
<?xml version='1.0' standalone='yes'?>
<meetings>
    <current></current>
    <next></next>
</meetings>
XML;
$xmlElement = new \SimpleXMLElement($xml);

if ($data['current'] !== null) {
    foreach ($data['current'] as $key => $value) {
        $xmlElement->current->addChild($key, htmlspecialchars($value));
    }
}
if ($data['next'] !== null) {
    foreach ($data['next'] as $key => $value) {
        $xmlElement->next->addChild($key, htmlspecialchars($value));
    }
}

echo $xmlElement->asXML();
