<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

define('_APP_', realpath(__DIR__ . '/../app'));
require_once __DIR__.'/../vendor/autoload.php';
$config = require_once _APP_ . '/config.php';

/* @var $app Silex\Application */
$app = new Silex\Application();
$app['debug'] = true;

$view = new League\Plates\Engine(__DIR__ . '/../views');
$db = new \Medoo\Medoo([
  // required
  'database_type' => 'mysql',
  'database_name' => $config['db']['dbname'],
  'server'        => $config['db']['host'],
  'username'      => $config['db']['username'],
  'password'      => $config['db']['password'],
  'charset'       => 'utf8'
]);

$app->match('/', function (Request $req) use ($app, $view, $db) {

  $date = $req->get('date', date('Y-m-d'));

  // Сохранение новой брони:
  if($req->getMethod() == 'POST') {

    if($req->request->get('del')) {
      $db->delete("reserve", ["id" => $req->request->get("reserve_id")]);
      return $app->redirect('/?date=' . $req->request->get('date'));
    } else {
      $reserve = [
        'room_id' => $req->request->get('room_id'),
        'dt_from' => $req->request->get('date') . ' ' . $req->request->get('from_hour') . ':' . $req->request->get('from_minute') . ':00',
        'dt_to'   => $req->request->get('date') . ' ' . $req->request->get('to_hour') . ':' . $req->request->get('to_minute') . ':00',
        'comment' => $req->request->get('comment'),
        'repeated'=> $req->request->get('repeat') ?: null
      ];
      $db->insert("reserve", $reserve);
      return $app->redirect('/?date=' . $req->request->get('date'));
    }
  }



  /*$stmt = $db->query("SELECT * FROM room ORDER BY name");
  $rooms = $stmt->fetchAll(\PDO::FETCH_ASSOC);*/

  $meetroomsPath = __DIR__ . '/../data/meetrooms.json';
  $rooms = json_decode(file_exists($meetroomsPath) ? file_get_contents($meetroomsPath) : '[]');

  /*$dayNames = array_map(function ($room) {
    return $room['name'];
  }, $rooms);*/
  $dayNames = $rooms;

  $fullDayNames = $dayNames;
  $hourMin      = 7;
  $hourMax      = 23;
  $dayStart     = $hourMin * 60;
  $dayLength    = $hourMax * 60 - $dayStart;

  $hours = array();
  for($h = $hourMin; $h < $hourMax; $h ++) {
    $hours[] = array('hour' => $h, 'from' => $h * 60, 'to'  => ($h + 1) * 60);
  }

  /*$stmt = $db->pdo->prepare("
  SELECT reserve.*, room.name 
  FROM reserve 
  JOIN room on room.id = reserve.room_id 
  WHERE repeated IS NULL AND dt_from >= :dt AND dt_from < DATE_ADD(:dt, INTERVAL 1 DAY) ORDER BY dt_from");
  $stmt->execute([
    'dt' => $date
  ]);
  $reserves = $stmt->fetchAll(PDO::FETCH_ASSOC);*/

  $client = new Google_Client([
    'application_name' => 'Google Calendar API PHP Quickstart',
    'access_type' => 'online',
    'redirect_uri' => 'http://' . $req->getHttpHost() . '/google-calendar/'
  ]);
  $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
  $client->setAuthConfig(__DIR__ . '/../client_secret_web.json');

  // Load previously authorized credentials from a file.
  $credentialsPath = __DIR__ . '/../data/calendar-php-quickstart.json';
  $meetroomsPath = __DIR__ . '/../data/meetrooms.json';

  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    return new RedirectResponse($client->createAuthUrl());
  }

  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $refreshTokenSaved = $client->getRefreshToken();
    if($refreshTokenSaved) {
      $client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);
      $accessTokenUpdated = $client->getAccessToken();
      $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;

      file_put_contents($credentialsPath, json_encode($accessTokenUpdated));
    } else {
      return new RedirectResponse($client->createAuthUrl());
    }
  }

  $service = new Google_Service_Calendar($client);
  $dt = new DateTime($date);
  $list = $service->calendarList->listCalendarList();
  $reserves = array();
  foreach ($list as $calendar) {
    if(in_array($calendar->id, $rooms)) {
      $events = $service->events->listEvents($calendar->id, array(
        'maxResults' => 999,
        'orderBy' => 'startTime',
        'singleEvents' => TRUE,
        'timeMin' => $dt->format('c'),
        'timeMax' => $dt->add(new DateInterval('P1D'))->format('c'),
      ));
      foreach ($events as $event) {
        $reserves[] = [
          'room_id' => $calendar->id,
          'dt_from' => $event->start->dateTime,
          'dt_to'   => $event->end->dateTime,
          'comment' => $event->summary
        ];
      }
    }
  }


  /*// Каждую неделю:
  $stmt = $db->pdo->prepare("
  SELECT reserve.*, room.name 
  FROM reserve 
  JOIN room on room.id = reserve.room_id 
  WHERE 
    repeated = 'week' AND 
    dt_from <= :dt AND 
    DAYOFWEEK(dt_from) = DAYOFWEEK(:dt)");
  $stmt->execute([
    'dt' => $date . ' 23:59:59'
  ]);
  $reserves = array_merge($reserves, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

  // Каждый день:
  $stmt = $db->pdo->prepare("
  SELECT reserve.*, room.name 
  FROM reserve 
  JOIN room on room.id = reserve.room_id 
  WHERE 
    repeated = 'day' AND 
    dt_from <= :dt
  ");
  $stmt->execute([
    'dt' => $date . ' 23:59:59'
  ]);
  $reserves = array_merge($reserves, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

  // Каждый месяц:
  $stmt = $db->pdo->prepare("
  SELECT reserve.*, room.name 
  FROM reserve 
  JOIN room on room.id = reserve.room_id 
  WHERE 
    repeated = 'month' AND
    DAYOFMONTH(dt_from) = DAYOFMONTH(:dt) AND
    dt_from <= :dt
  ");
  $stmt->execute([
    'dt' => $date . ' 23:59:59'
  ]);
  $reserves = array_merge($reserves, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);*/

  $days = array();
  for($d = 0; $d < count($rooms); $d++) {
    $day = [
      'id'       => $rooms[$d],
      'name'     => $rooms[$d],
      'fullname' => $rooms[$d],
      'hours'    => array(),
      'slots'    => array()
    ];

    $slots = [];
    foreach ($reserves as $reserve) {
      if($reserve['room_id'] == $rooms[$d]) {
        $slots[] = $reserve;
      }
    }

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
      $slot['title'] = $fullDayNames[$d] . ' с ' . date('H:i', mktime($fromHour, $fromMinute)) . ' до ' . date('H:i', mktime($toHour, $toMinute));

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
  return $view->render("index", [
    "body" => $html,
    "date" => $date,
    "rooms" => $rooms,
    "reserves" => $reserves
  ]);
});

$app->match('/google-calendar/', function (Request $req) use ($app, $view) {

  $client = new Google_Client([
    'application_name' => 'Google Calendar API PHP Quickstart',
    'access_type' => 'online',
    'redirect_uri' => 'http://' . $req->getHttpHost() . '/google-calendar/'
  ]);
  $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
  $client->setAuthConfig(__DIR__ . '/../client_secret_web.json');

  // Load previously authorized credentials from a file.
  $credentialsPath = __DIR__ . '/../data/calendar-php-quickstart.json';
  $meetroomsPath = __DIR__ . '/../data/meetrooms.json';

  if($req->get('code')) {
    $accessToken = $client->fetchAccessTokenWithAuthCode($req->get('code'));
    file_put_contents($credentialsPath, json_encode($accessToken));
    return new RedirectResponse('/google-calendar/');
  }

  if($req->getMethod() == 'POST') {
    $calendars = $req->request->get('calendar');
    file_put_contents($meetroomsPath, json_encode($calendars));
    return new RedirectResponse('/google-calendar/');
  }

  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    return new RedirectResponse($client->createAuthUrl());
  }

  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $refreshTokenSaved = $client->getRefreshToken();
    if($refreshTokenSaved) {
      $client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);
      $accessTokenUpdated = $client->getAccessToken();
      $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;

      file_put_contents($credentialsPath, json_encode($accessTokenUpdated));
    } else {
      return new RedirectResponse($client->createAuthUrl());
    }
  }

  $service = new Google_Service_Calendar($client);

  // Print the next 10 events on the user's calendar.
  $dt = new DateTime(date('Y-m-d 00:00:00'));
  $list = $service->calendarList->listCalendarList();
  $meetrooms = json_decode(file_exists($meetroomsPath) ? file_get_contents($meetroomsPath) : '[]');
  $events = array();
  foreach ($list as $calendar) {
    if(in_array($calendar->id, $meetrooms)) {
      $events = $service->events->listEvents($calendar->id, array(
        'maxResults' => 999,
        'orderBy' => 'startTime',
        'singleEvents' => TRUE,
        'timeMin' => $dt->format('c'),
        'timeMax' => $dt->add(new DateInterval('P1D'))->format('c'),
      ));
    }
  }

  if(empty($meetrooms) && $list->count()) {
    return $view->render('google_calendar_select', [
      'calendars' => $list
    ]);
  } else {
    return $view->render('google_calendar_events', [
      'events' => $events
    ]);
  }
});

$app->run();
