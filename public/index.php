<?php
use Symfony\Component\HttpFoundation\Request;

define('_APP_', realpath(__DIR__ . '/../app'));
require_once __DIR__.'/../vendor/autoload.php';
$config = require_once _APP_ . '/config.php';

/* @var $app Silex\Application */
$app = new Silex\Application();

$view = new League\Plates\Engine(__DIR__ . '/../views');
$db = new medoo([
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
      ];
      $db->insert("reserve", $reserve);
      return $app->redirect('/?date=' . $req->request->get('date'));
    }
  }

  $stmt = $db->query("SELECT * FROM room ORDER BY name");
  $rooms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

  $dayNames = array_map(function ($room) {
    return $room['name'];
  }, $rooms);

  $fullDayNames = $dayNames;
  $hourMin      = 7;
  $hourMax      = 23;
  $dayStart     = $hourMin * 60;
  $dayLength    = $hourMax * 60 - $dayStart;

  $hours = array();
  for($h = $hourMin; $h < $hourMax; $h ++) {
    $hours[] = array('hour' => $h, 'from' => $h * 60, 'to'  => ($h + 1) * 60);
  }

  $stmt = $db->pdo->prepare("SELECT reserve.*, room.name FROM reserve JOIN room on room.id = reserve.room_id WHERE dt_from >= :dt AND dt_from < DATE_ADD(:dt, INTERVAL 1 DAY) ORDER BY dt_from");
  $stmt->execute([
    'dt' => $date
  ]);
  $reserves = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $days = array();
  for($d = 0; $d < count($dayNames); $d++) {
    $day = [
      'name'     => $dayNames[$d],
      'fullname' => $fullDayNames[$d],
      'hours'    => array(),
      'slots'    => array()
    ];

    $slots = [];
    foreach ($reserves as $reserve) {
      if($reserve['room_id'] == $rooms[$d]['id']) {
        $slots[] = $reserve;
      }
    }

    foreach ($slots as $slot) {
      $slot['from'] = ( strtotime($slot['dt_from']) - strtotime($date) ) / 60;
      $slot['to']   = ( strtotime($slot['dt_to']) - strtotime($date) ) / 60;

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
      $html .= '<div class="calender-hour"><span>'.$hours[$k]['hour'].'</span></div>';
    }
  }
  // Последний час надо врисовать в его "предшественника":
  $html .= '<div class="calender-hour last-hour"><span>'.($hours[$k]['hour'] + 1).'</span></div>';
  $html .= '</div>';
  foreach($days as $day) {
    $html .=
      '<div class="calendar-day">' .
      '<div class="calendar-dayname">'. $day['name'] .'</div>';
    $html .= str_repeat('<div class="calender-hour"></div>', count($hours));
    foreach($day['slots'] as $slot) { $html .= '<div class="calender-slot" style="left:'.$slot['left'].'%; width:'.$slot['width'].'%" title="'.$slot['title'].'"></div>'; }
    $html .= '</div>';
  }
  $html .= '</div>';
  return $view->render("index", [
    "body" => $html,
    "date" => $date,
    "rooms" => [
      ["id" => 1, "name" => "Большая"],
      ["id" => 2, "name" => "У окна"],
      ["id" => 3, "name" => "Рыцари"],
      ["id" => 4, "name" => "HR"]
    ],
    "reserves" => $reserves
  ]);
});

$app->run();
