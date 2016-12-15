<?php

require_once __DIR__.'/../vendor/autoload.php';

/* @var $app Silex\Application */
$app = new Silex\Application();

$view = new League\Plates\Engine(__DIR__ . '/../views');

$db = new PDO('mysql:dbname=meetroom;host=localhost', 'meetroom', 'meetpass');


$app->match('/', function () use ($app, $view, $db) {

  $slots = json_decode('{
    "1": [],
    "2": [
      {
        "from": 420,
        "to": 1345,
        "strict": "free"
      }
    ],
    "3": {
      "0": {
        "from": 420,
        "to": 480,
        "strict": "free"
      }      
    },
    "4": [
      {
        "from": 420,
        "to": 540,
        "strict": "free"
      }      
    ],
    "5": [
      {
        "from": 480,
        "to": 540,
        "strict": "free"
      }      
    ],
    "6": [
      
    ],
    "7": [
      {
        "from": 900,
        "to": 1140,
        "strict": "free"
      },
      {
        "from": 1200,
        "to": 1380,
        "strict": "free"
      }    
    ]
  }', true);

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

  $days = array();
  for($d = 0; $d < count($dayNames); $d++) {
    $day = [
      'name'     => $dayNames[$d],
      'fullname' => $fullDayNames[$d],
      'hours'    => array(),
      'slots'    => array()
    ];

    $stmt = $db->query("SELECT * FROM reserve WHERE room_id = " . $rooms[$d]['id'] . " ORDER BY dt_from");
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($slots as $slot) {
      $slot['from'] = ( strtotime($slot['dt_from']) - strtotime(date('Y-m-d')) ) / 60;
      $slot['to']   = ( strtotime($slot['dt_to']) - strtotime(date('Y-m-d')) ) / 60;

      var_dump($slot);
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
    "rooms" => [
      ["id" => 1, "name" => "Большая"],
      ["id" => 2, "name" => "У окна"],
      ["id" => 3, "name" => "Рыцари"],
      ["id" => 4, "name" => "HR"]
    ]
  ]);
});

$app->run();
