<?php

namespace uguranyum\icalender;

use Yii;

class iCalender{
    /*
    * How many ToDos are in this ical?
    */
    public $todo_count = 0;
    /*
    * How many events are in this ical?
    */
    public $event_count = 0;
    /*
    * The parsed calendar
    */
    public $cal;
    /*
    * Which keyword has been added to cal at last?
    */
    private $last_keyword;
    /*
    * Yii2 vendor icalender url
    */
    public $vendorurl = '';
    /*
    * Yii2 icalender  temp  url
    */
    public $vendortempurl = '';
    /*
    * Temprary document url
    */
    public $temp_dir = '';

    /*
     * Lets Start
     */
    public function __construct($url){
        $this->vendorurl        =  Yii::getAlias('@vendor').'/uguranyum/yii2-icalender/';
        $this->vendortempurl    =  Yii::getAlias('@vendor').'/uguranyum/yii2-icalender/temp/';


        $result     = $this->run($url);
        $readfile   = $this->lookFile($result);
        $insertfile = $this->insertDatabase($readfile);
        print_r($readfile);
    }

    public function run($url){
        $curl   = $this->saveFile($url);
        if ($curl == false)
            return 'Can not find the file';
        return $curl;
    }

    public function saveFile($url){
        if(!@file_get_contents($url))
            return false;

        $url_path   = explode('/',$url);
        $doc_ext    = count($url_path)-1;
        $new_path   = $this->vendortempurl.'/'.time().'_'.$url_path[$doc_ext];

        $this->temp_dir   = $new_path;

        file_put_contents($new_path, file_get_contents($url));
        return $new_path;
    }


    /**
     * @param $filename
     * @return bool
     */
    public function lookFile($filename)
    {
        if (!$filename)
            return false;

        $type = '';

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (stristr($lines[0], 'BEGIN:VCALENDAR') === false) {
            return false;
        } else {
            foreach ($lines as $line) {
                $line = trim($line);
                $add  = $this->keyValueFromString($line);
                if ($add === false) {
                    $this->addCalendarComponentWithKeyAndValue($type, false, $line);
                    continue;
                }

                list($keyword, $value) = $add;

                switch ($line) {
                    case "BEGIN:VTODO":
                        $this->todo_count++;
                        $type = "VTODO";
                        break;
                    case "BEGIN:VEVENT":
                        $this->event_count++;
                        $type = "VEVENT";
                        break;
                    case "BEGIN:VCALENDAR":
                    case "BEGIN:DAYLIGHT":
                    case "BEGIN:VTIMEZONE":
                    case "BEGIN:STANDARD":
                        $type = $value;
                        break;
                    case "END:VTODO":
                    case "END:VEVENT":
                    case "END:VCALENDAR":
                    case "END:DAYLIGHT":
                    case "END:VTIMEZONE":
                    case "END:STANDARD":
                        $type = "VCALENDAR";
                        break;
                    default:
                        $this->addCalendarComponentWithKeyAndValue($type, $keyword, $value);
                        break;
                }
            }
            return $this->cal;
        }
    }

   public function addCalendarComponentWithKeyAndValue($component, $keyword, $value)
    {
        if ($keyword == false) {
            $keyword = $this->last_keyword;
            switch ($component) {
                case 'VEVENT':
                    $value = $this->cal[$component][$this->event_count - 1]
                        [$keyword].$value;
                    break;
                case 'VTODO' :
                    $value = $this->cal[$component][$this->todo_count - 1]
                        [$keyword].$value;
                    break;
            }
        }

        if (stristr($keyword, "DTSTART") or stristr($keyword, "DTEND")) {
            $keyword = explode(";", $keyword);
            $keyword = $keyword[0];
        }

        switch ($component) {
            case "VTODO":
                $this->cal[$component][$this->todo_count - 1][$keyword] = $value;
                //$this->cal[$component][$this->todo_count]['Unix'] = $unixtime;
                break;
            case "VEVENT":
                $this->cal[$component][$this->event_count - 1][$keyword] = $value;
                break;
            default:
                $this->cal[$component][$keyword] = $value;
                break;
        }
        $this->last_keyword = $keyword;
    }


    public function keyValueFromString($text)
    {
        preg_match("/([^:]+)[:]([\w\W]*)/", $text, $matches);
        if (count($matches) == 0) {
            return false;
        }
        $matches = array_splice($matches, 1, 2);
        return $matches;
    }


    public function iCalDateToUnixTimestamp($icalDate)
    {
        $icalDate = str_replace('T', '', $icalDate);
        $icalDate = str_replace('Z', '', $icalDate);

        $pattern  = '/([0-9]{4})';   // 1: YYYY
        $pattern .= '([0-9]{2})';    // 2: MM
        $pattern .= '([0-9]{2})';    // 3: DD
        $pattern .= '([0-9]{0,2})';  // 4: HH
        $pattern .= '([0-9]{0,2})';  // 5: MM
        $pattern .= '([0-9]{0,2})/'; // 6: SS
        preg_match($pattern, $icalDate, $date);

        // Unix timestamp can't represent dates before 1970
        if ($date[1] <= 1970) {
            return false;
        }
        // Unix timestamps after 03:14:07 UTC 2038-01-19 might cause an overflow
        // if 32 bit integers are used.
        $timestamp = mktime((int)$date[4],
            (int)$date[5],
            (int)$date[6],
            (int)$date[2],
            (int)$date[3],
            (int)$date[1]);
        return  $timestamp;
    }

    public function events()
    {
        $array = $this->cal;
        return $array['VEVENT'];
    }

    public function hasEvents()
    {
        return ( count($this->events()) > 0 ? true : false );
    }

    public function eventsFromRange($rangeStart = false, $rangeEnd = false)
    {
        $events = $this->sortEventsWithOrder($this->events(), SORT_ASC);

        if (!$events) {
            return false;
        }

        $extendedEvents = array();

        if ($rangeStart !== false) {
            $rangeStart = new DateTime();
        }

        if ($rangeEnd !== false or $rangeEnd <= 0) {
            $rangeEnd = new DateTime('2038/01/18');
        } else {
            $rangeEnd = new DateTime($rangeEnd);
        }

        $rangeStart = $rangeStart->format('U');
        $rangeEnd   = $rangeEnd->format('U');



        // loop through all events by adding two new elements
        foreach ($events as $anEvent) {
            $timestamp = $this->iCalDateToUnixTimestamp($anEvent['DTSTART']);
            if ($timestamp >= $rangeStart && $timestamp <= $rangeEnd) {
                $extendedEvents[] = $anEvent;
            }
        }

        return $extendedEvents;
    }

    public function sortEventsWithOrder($events, $sortOrder = SORT_ASC)
    {
        $extendedEvents = array();

        // loop through all events by adding two new elements
        foreach ($events as $anEvent) {
            if (!array_key_exists('UNIX_TIMESTAMP', $anEvent)) {
                $anEvent['UNIX_TIMESTAMP'] =
                    $this->iCalDateToUnixTimestamp($anEvent['DTSTART']);
            }

            if (!array_key_exists('REAL_DATETIME', $anEvent)) {
                $anEvent['REAL_DATETIME'] =
                    date("d.m.Y", $anEvent['UNIX_TIMESTAMP']);
            }

            $extendedEvents[] = $anEvent;
        }

        foreach ($extendedEvents as $key => $value) {
            $timestamp[$key] = $value['UNIX_TIMESTAMP'];
        }
        array_multisort($timestamp, $sortOrder, $extendedEvents);

        return $extendedEvents;
    }

    public function insertDatabase($readfile){
        foreach($readfile as $calender_keys => $calender_values){
            if($calender_keys == 'VCALENDAR'){ //foreach($calender_values as $vcal_ley => $vcal_value)
                $readfile['created_at'] = date("Y-m-d H:i:s");
                Yii::$app->db->createCommand()->insert('icalender_main',$readfile['VCALENDAR'])->execute();
            }
        }
    }

}