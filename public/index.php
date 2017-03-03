<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

define('_APP_', realpath(__DIR__ . '/../App'));
require_once __DIR__.'/../vendor/autoload.php';
$config = require_once _APP_ . '/config.php';

/* @var $app Silex\Application */
$app = new Silex\Application();
$app['config'] = $config;
$app['debug'] = true;
$app['view'] = function () {
  return new League\Plates\Engine(__DIR__ . '/../views');
};
$app['client'] = function ($app) {

  // Fetch data from google calendar
  $client = new Google_Client([
    'application_name' => $app['config']['google']['app_name'],
    'access_type'      => 'offline'
  ]);
  $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
  $client->setAuthConfig($app['config']['storage']['app_secret']);
  $credentialsPath = $app['config']['storage']['app_credentials'];

  if (file_exists($credentialsPath)) {

    $accessToken = json_decode(file_get_contents($credentialsPath), true);
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
      $accessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
      $client->setAccessToken($accessToken);
      file_put_contents($credentialsPath, json_encode($accessToken));
    }
  }
  return $client;
};

$app->match('/', function (Request $req, Silex\Application $app) {

  $date = $req->get('date', date('Y-m-d'));

  // Load chosen calendars
  $calendarsFilename = $app['config']['storage']['calendars'];
  $calendars = json_decode(file_exists($calendarsFilename) ? file_get_contents($calendarsFilename) : '[]');

  $hourMin = $app['config']['hours']['min'];
  $hourMax = $app['config']['hours']['max'];

  // Make hours grid
  $hours   = array();
  for($h = $hourMin; $h < $hourMax; $h ++) {
    $hours[] = array('hour' => $h, 'from' => $h * 60, 'to'  => ($h + 1) * 60);
  }

  /* @var $client Google_Client */
  $client = $app['client'];
  $client->setRedirectUri('http://' . $req->getHttpHost() . '/google-calendar/');
  if( !$client->getAccessToken() ) {
    return new RedirectResponse($client->createAuthUrl());
  }

  $service = new \App\Model\Service($client);
  $service->setActiveCalendars($calendars);
  $events = $service->getEventsFor($date);

  $dayStart = $hourMin * 60;
  $dayLength = $hourMax * 60 - $dayStart;
  $rooms = $service->getRooms();
  $days = array();
  for($d = 0; $d < count($rooms); $d++) {
    $room = $rooms[$d];
    $day = [
      'id'       => $room['id'],
      'name'     => $room['name'],
      'fullname' => $room['name'],
      'hours'    => array(),
      'slots'    => array()
    ];

    $slots = array_filter($events, function ($event) use ($room) {
      return $event['room_id'] == $room['id'];
    });

    foreach ($slots as $slot) {

      $slot['from'] = ( strtotime($date . ' ' . date('H:i:s', strtotime($slot['dt_from']))) - strtotime($date) ) / 60;
      $slot['to']   = ( strtotime($date . ' ' . date('H:i:s', strtotime($slot['dt_to']))) - strtotime($date) ) / 60;

      // На всякий случай убедимся, что это именно "свободный" слот:
      // Расчёт ширины слота (в процентах) и его левого смещения:
      $slot['width'] = 100 * ($slot['to'] - $slot['from']) / $dayLength;
      $slot['left']  = 100 * ($slot['from'] - $dayStart) / $dayLength;

      // Нам нужен какой-нибудь поясняющий заголовок для каждого слота.
      // Например: "Понедельник, с 8 до 11"
      $fromHour   = floor($slot['from'] / 60);
      $fromMinute = $slot['from'] - $fromHour * 60;
      $toHour     = floor($slot['to'] / 60);
      $toMinute   = $slot['to'] - $toHour * 60;
      $slot['title'] = $room['name'] . ' с ' . date('H:i', mktime($fromHour, $fromMinute)) . ' до ' . date('H:i', mktime($toHour, $toMinute));

      $day['slots'][] = $slot;
    }

    $days[] = $day;
  }

  $html = '';
  $html .=
    '<div class="calendar">
            <div class="calendar-day calendar-day-head">
                <div class="calendar-dayname"></div>';
  $hc = count($hours);
  for($k = 0; $k < $hc - 1; $k++) {
    // Рисуем только нечётные часы (7, 9, 11, ... ) кроме последнего ($hc - 1)
    if( ($k + 1) % 2 == 0) {
      $html .= '<div class="calender-hour">&nbsp;</div>';
    } else {
      $html .= '<div class="calender-hour"><span>'.$hours[$k]['hour'].'<sup>00</sup></span></div>';
    }
  }
  // Последний час надо врисовать в его "предшественника":
  $html .= '<div class="calender-hour last-hour"><span>'.($hours[$k]['hour'] + 1).'</span></div>';
  $html .= '</div>';
  foreach($days as $day) {
    $html .=
      '<div class="calendar-day">' .
      '<div class="calendar-dayname" data-id="'.$day['id'].'">'. $day['name'] .'</div>';
    foreach ($hours as $hour) {
      $html .= '<div class="calender-hour" data-hour="'.$hour['hour'].'"></div>';
    }
    foreach($day['slots'] as $slot) {
      $html .= sprintf('<div class="calender-slot" style="left:%s%%; width:%s%%" title="%s" data-toggle="popover" data-placement="bottom" data-trigger="hover" data-content="%s"></div>',$slot['left'], $slot['width'], $slot['title'], $slot['comment']);
    }
    $html .= '</div>';
  }
  $html .= '</div>';
  return $app['view']->render("index", [
    "body" => $html,
    "date" => $date,
    "rooms" => $rooms,
    "reserves" => $events
  ]);
});

$app->get('/google-calendar/', function (Request $req, Silex\Application $app) {

  /* @var $client Google_Client */
  $client = $app['client'];

  // Load previously authorized credentials from a file.
  $credentialsPath = $app['config']['storage']['app_credentials'];

  // Success redirect from google calendar
  if($req->get('code')) {
    $accessToken = $client->fetchAccessTokenWithAuthCode($req->get('code'));
    file_put_contents($credentialsPath, json_encode($accessToken));
    return new RedirectResponse('/select-rooms/');
  }
});

$app->match('/select-rooms/', function (Request $req, Silex\Application $app) {

  /* @var $client Google_Client */
  $client = $app['client'];

  if( !$client->getAccessToken() ) {
    throw new Exception("No access token", 404);
  }

  $calendarsPath   = $app['config']['storage']['calendars'];
  // Select active calendars as "rooms"
  if($req->getMethod() == 'POST') {
    $calendars = $req->request->get('calendar');
    file_put_contents($calendarsPath, json_encode($calendars));
    return new RedirectResponse('/');
  }

  $service = new Google_Service_Calendar($client);
  $list = $service->calendarList->listCalendarList();
  return $app['view']->render('google_calendar_select', [
    'calendars' => $list
  ]);
});

$app->run();
