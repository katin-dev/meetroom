<?php

namespace App\Model;

class Service extends \Google_Service_Calendar
{
  private $activeCalendars = [];
  private $cachedCalendarList;

  public function setActiveCalendars(array $calendars) {
    $this->activeCalendars = $calendars;
  }

  public function getEventsFor($date) {
    $events = array();
    foreach ($this->getCalendarList() as $calendar) {
      if(($key = array_search($calendar->id, $this->activeCalendars)) !== false) {
        $dt = new \DateTime($date);
        $eventList = $this->events->listEvents($calendar->id, array(
          'maxResults' => 999,
          'orderBy' => 'startTime',
          'singleEvents' => TRUE,
          'timeMin' => $dt->format('c'),
          'timeMax' => $dt->add(new \DateInterval('P1D'))->format('c'),
        ));
        foreach ($eventList as $event) {
          $events[] = [
            'room_id' => $calendar->id,
            'dt_from' => $event->start->dateTime,
            'dt_to'   => $event->end->dateTime,
            'comment' => $event->summary
          ];
        }
      }
    }

    return $events;
  }

  /**
   * Get cached calendarList
   * @return \Google_Service_Calendar_CalendarList
   */
  private function getCalendarList() {
    if( !$this->cachedCalendarList ) {
      $this->cachedCalendarList = $this->calendarList->listCalendarList();
    }
    return $this->cachedCalendarList;
  }


  /**
   * Get rooms array based on activeCalendars
   * @return array
   */
  public function getRooms() {
    $rooms = [];
    foreach ($this->getCalendarList() as $calendar) {
      if (($key = array_search($calendar->id, $this->activeCalendars)) !== false) {
        $rooms[$key] = isset($rooms[$calendar->id]) ? $rooms[$calendar->id] : [
          'id' => $calendar->id,
          'name' => $calendar->summary ?: $calendar->id
        ];
      }
    }
    return $rooms;
  }
}