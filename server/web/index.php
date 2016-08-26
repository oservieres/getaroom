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
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
}

$service = new Google_Service_Calendar($client);

$calendarId = 'tea-ebook.com_2d3230323430343331393935@resource.calendar.google.com';

$currentDate = new \DateTimeImmutable("today", new \DateTimeZone('UTC'));
$currentTime = new \DateTimeImmutable(null, new \DateTimeZone('UTC'));
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => $currentDate->format("c"),
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
    $start = new \DateTimeImmutable($start, new \DateTimeZone('UTC'));
    $end = $event->end->dateTime;
    if (empty($end)) {
      $end = $event->end->date;
    }
    $end = new \DatetimeImmutable($end, new \DateTimeZone('UTC'));

    $eventType = null;
    if ($start < $currentTime && $end > $currentTime) {
        $data['current'] = [
            'summary' => $event->getSummary(),
            'creator' => $event->getCreator()->getEmail(),
            'start' => $start->format('c'),
            'end' => $end->format('c'),
        ];
    } else if ($start > $currentTime) {
        $data['next'] = [
            'summary' => $event->getSummary(),
            'creator' => $event->getCreator()->getEmail(),
            'start' => $start->format('c'),
            'end' => $end->format('c'),
        ];
        break;
    }

  }
}

header("HTTP/1.1 200 OK");
header('Content-Type: application/json');
echo json_encode($data);
