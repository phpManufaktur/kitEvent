<?php

/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
    if (defined('LEPTON_VERSION'))
        include (WB_PATH . '/framework/class.secure.php');
} else {
    $oneback = "../";
    $root = $oneback;
    $level = 1;
    while (($level < 10) && (! file_exists(
            $root . '/framework/class.secure.php'))) {
        $root .= $oneback;
        $level += 1;
    }
    if (file_exists($root . '/framework/class.secure.php')) {
        include ($root . '/framework/class.secure.php');
    } else {
        trigger_error(
                sprintf("[ <b>%s</b> ] Can't include class.secure.php!",
                        $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
    }
}
// end include class.secure.php

require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) .
         '/initialize.php');

class monthlyCalendar
{

    const REQUEST_ACTION = 'kea';

    const REQUEST_EVENT = 'evt';

    const REQUEST_YEAR = 'y';

    const REQUEST_MONTH = 'm';

    const REQUEST_DAY = 'd';

    const REQUEST_EVENT_ID = 'id';

    const ACTION_SHOW_MONTH = 'month';

    const ACTION_DEFAULT = 'def';

    const ACTION_SHOW_LIST = 'list';

    const ACTION_ORDER = 'ord';

    const EVENT_DAY = 'day';

    const EVENT_MONTH = 'month';

    private $error = '';

    private static $template_path = '';

    private $page_link;

    private $response_link;

    const PARAM_SHOW_WEEKS = 'show_weeks';

    const PARAM_INACTIVE_DAYS = 'inactive_days';

    const PARAM_NAVIGATION = 'navigation';

    const PARAM_SHOW_TODAY = 'show_today';

    const PARAM_RESPONSE_ID = 'response_id';

    const PARAM_IGNORE_TOPICS = 'ignore_topics';

    const PARAM_SELECT_MONTH = 'month';

    const PARAM_SELECT_YEAR = 'year';

    const PARAM_GROUP = 'group';

    const PARAM_ACTION = 'action';

    const PARAM_PRESET = 'preset';

    const PARAM_LINK_MONTH = 'link_month';

    const PARAM_DEBUG = 'debug';

    const PARAM_CSS = 'css';

    private $params = array(
            self::PARAM_SHOW_WEEKS => true,
            self::PARAM_INACTIVE_DAYS => true,
            self::PARAM_NAVIGATION => true,
            self::PARAM_SHOW_TODAY => true,
            self::PARAM_RESPONSE_ID => - 1,
            self::PARAM_IGNORE_TOPICS => false,
            self::PARAM_SELECT_MONTH => 0,
            self::PARAM_SELECT_YEAR => 0,
            self::PARAM_GROUP => '',
            self::PARAM_ACTION => self::ACTION_SHOW_MONTH,
            self::PARAM_PRESET => 1,
            self::PARAM_LINK_MONTH => false,
            self::PARAM_DEBUG => false,
            self::PARAM_CSS => true
    );

    protected $lang = null;

    public function __construct ()
    {
        global $kitLibrary;
        global $I18n;

        self::$template_path = WB_PATH . '/modules/' .
                 basename(dirname(__FILE__)) . '/templates/frontend/presets/';
        $kitLibrary->getUrlByPageID(PAGE_ID, $this->page_link);
        date_default_timezone_set(CFG_TIME_ZONE);
        $this->lang = $I18n;
    } // __construct()
    public function getParams ()
    {
        return $this->params;
    } // getParams()
    public function setParams ($params = array())
    {
        $this->params = $params;
    } // setParams()

    /**
     * Set $this->error to $error
     *
     * @param STR $error
     */
    public function setError ($error)
    {
        $caller = next(debug_backtrace());
        $this->error = sprintf('[%s::%s - %s] %s', basename($caller['file']),
                $caller['function'], $caller['line'], $error);
    } // setError()

    /**
     * Get Error from $this->error;
     *
     * @return STR $this->error
     */
    public function getError ()
    {
        return $this->error;
    } // getError()

    /**
     * Check if $this->error is empty
     *
     * @return BOOL
     */
    public function isError ()
    {
        return (bool) ! empty($this->error);
    } // isError

    /**
     * Verhindert XSS Cross Site Scripting
     *
     * @param REFERENCE $_REQUEST
     *            Array
     * @return $request
     */
    public function xssPrevent (&$request)
    {
        if (is_string($request)) {
            $request = html_entity_decode($request);
            $request = strip_tags($request);
            $request = trim($request);
            $request = stripslashes($request);
        }
        return $request;
    } // xssPrevent()

    /**
     * Execute the desired template and return the completed template
     */
    protected function getTemplate ($template, $template_data)
    {
        global $parser;

        $template_path = self::$template_path . $this->params[self::PARAM_PRESET] .
                 '/' . LANGUAGE . '/' . $template;
        if (! file_exists($template_path)) {
            // template does not exist - fallback to default language!
            $template_path = self::$template_path .
                     $this->params[self::PARAM_PRESET] . '/DE/' . $template;
            if (! file_exists($template_path)) {
                // template does not exists - fallback to the default preset!
                $template_path = self::$template_path . '1/' . LANGUAGE . '/' .
                         $template;
                if (! file_exists($template_path)) {
                    // template does not exists - fallback to the default preset
                    // and the default language
                    $template_path = self::$template_path . '1/DE/' . $template;
                    if (! file_exists($template_path)) {
                        // template does not exists in any possible path - give
                        // up!
                        $this->setError(
                                sprintf('[%s - %s] %s', __METHOD__, __LINE__,
                                        $this->lang->translate(
                                                'Error: The template {{ template }} does not exists in any of the possible paths!',
                                                array(
                                                        'template',
                                                        $template
                                                ))));
                        return false;
                    }
                }
            }
        }

        // add the template_path to the $template_data (for debugging purposes)
        if (! isset($template_data['template_path']))
            $template_data['template_path'] = $template_path;
            // add the debug flag to the $template_data
        if (! isset($template_data['DEBUG']))
            $template_data['DEBUG'] = (int) $this->params[self::PARAM_DEBUG];

        try {
            // try to execute the template with Dwoo
            $result = $parser->get($template_path, $template_data);
        } catch (Exception $e) {
            // prompt the Dwoo error
            $this->setError(
                    sprintf('[%s - %s] %s', __METHOD__, __LINE__,
                            $this->lang->translate(
                                    'Error executing template <b>{{ template }}</b>:<br />{{ error }}',
                                    array(
                                            'template' => $template,
                                            'error' => $e->getMessage()
                                    ))));
            return false;
        }
        return $result;
    } // getTemplate()
    public function action ()
    {

        /**
         * to prevent cross site scripting XSS it is important to look also to
         * $_REQUESTs which are needed by other KIT addons.
         * Addons which need
         * a $_REQUEST with HTML must set this key in
         * $_SESSION['KIT_HTML_REQUEST']
         */
        $html_allowed = array();
        if (isset($_SESSION['KIT_HTML_REQUEST']))
            $html_allowed = $_SESSION['KIT_HTML_REQUEST'];
        $html = array();
        foreach ($html as $key)
            $html_allowed[] = $key;
        $_SESSION['KIT_HTML_REQUEST'] = $html_allowed;
        foreach ($_REQUEST as $key => $value) {
            if (! in_array($key, $html_allowed)) {
                $_REQUEST[$key] = $this->xssPrevent($value);
            }
        }

        // CSS laden?
        if ($this->params[self::PARAM_CSS]) {
            if (! is_registered_droplet_css('kit_monthly_calendar', PAGE_ID)) {
                register_droplet_css('kit_monthly_calendar', PAGE_ID,
                        'kit_event', 'kit_event.css');
            }
        } elseif (is_registered_droplet_css('kit_monthly_calendar', PAGE_ID)) {
            unregister_droplet_css('kit_monthly_calendar', PAGE_ID);
        }

        $action = (isset($this->params[self::PARAM_ACTION])) ? $this->params[self::PARAM_ACTION] : self::ACTION_SHOW_MONTH;
        if (isset($_REQUEST[self::REQUEST_ACTION]))
            $action = $_REQUEST[self::REQUEST_ACTION];

        switch ($action) :
            case self::ACTION_SHOW_LIST:
                $result = $this->showList();
                break;
            case self::ACTION_SHOW_MONTH:
            default:
                $result = $this->showCalendar();
                break;
        endswitch
        ;

        if ($this->isError())
            $result = $this->getError();
        return $result;
    } // action()

    private function getEvents ($month, $year, $group = '', $is_sheet = true)
    {
        global $dbEvent;
        global $dbEventGroup;

        $group = trim($group);
        $select_group = '';

        if (! empty($group)) {
            // ID der angegebenen Gruppe ermitteln
            $SQL = sprintf("SELECT %s FROM %s WHERE %s='%s' AND %s='%s'",
                    dbEventGroup::field_id, $dbEventGroup->getTableName(),
                    dbEventGroup::field_name, $group, dbEventGroup::field_status,
                    dbEventGroup::status_active);
            $groups = array();
            if (! $dbEventGroup->sqlExec($SQL, $groups)) {
                $this->setError($dbEventGroup->getError());
                return false;
            }
            if (count($groups) > 0) {
                $select_group = sprintf(" AND %s='%s'",
                        dbEvent::field_event_group,
                        $groups[0][dbEventGroup::field_id]);
            }
        }

        $ld = date('j', mktime(0, 0, 0, $month + 1, 0, $year));
        $SQL = sprintf(
                "SELECT %s FROM %s WHERE (%s>='%s' AND %s<='%s')%s AND %s='%s'",
                ($is_sheet) ? dbEvent::field_event_date_from : '*',
                $dbEvent->getTableName(), dbEvent::field_event_date_from,
                date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 1, $year)),
                dbEvent::field_event_date_from,
                date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $ld, $year)),
                $select_group, dbEvent::field_status, dbEvent::status_active);
        $events = array();
        if (! $dbEvent->sqlExec($SQL, $events)) {
            $this->setError($dbEvent->getError());
            return false;
        }
        if ($is_sheet) {
            $result = array();
            foreach ($events as $event) {
                $result[] = date('j',
                        strtotime($event[dbEvent::field_event_date_from]));
            }
            return $result;
        } else {
            return $events;
        }
    } // getEvents()

    public function showCalendar ()
    {
        global $kitLibrary;
        global $parser;

        if (($this->params[self::PARAM_SELECT_MONTH] > 0) &&
                 ($this->params[self::PARAM_SELECT_MONTH] < 13)) {
            $month = $this->params[self::PARAM_SELECT_MONTH];
        } elseif ($this->params[self::PARAM_SELECT_MONTH] < 0) {
            $month = date('n') + $this->params[self::PARAM_SELECT_MONTH];
        } elseif (($this->params[self::PARAM_SELECT_MONTH] > 100) &&
                 ($this->params[self::PARAM_SELECT_MONTH] < 112)) {
            $month = date('n') + ($this->params[self::PARAM_SELECT_MONTH] - 100);
        } else {
            $month = date('n');
        }

        if ($this->params[self::PARAM_SELECT_YEAR] == 0) {
            // 0 == use actual year
            $year = date('Y');
        } elseif ($this->params[self::PARAM_SELECT_YEAR] < 0) {
            // substract value from actual year
            $year = date('Y') + $this->params[self::PARAM_SELECT_YEAR];
        } elseif (($this->params[self::PARAM_SELECT_YEAR] > 0) &&
                 ($this->params[self::PARAM_SELECT_YEAR] < 100)) {
            $year = date('Y') + $this->params[self::PARAM_SELECT_YEAR];
        } else {
            $year = $this->params[self::PARAM_SELECT_YEAR];
        }

        if (isset($_REQUEST[self::REQUEST_MONTH]))
            $month = $_REQUEST[self::REQUEST_MONTH];
        if (isset($_REQUEST[self::REQUEST_YEAR]))
            $year = $_REQUEST[self::REQUEST_YEAR];

        $last_day_of_month = date('j', mktime(0, 0, 0, $month + 1, 0, $year));
        $month_name = $this->getMonthName($month);

        if ($this->params[self::PARAM_RESPONSE_ID] > 0) {
            $kitLibrary->getUrlByPageID($this->params[self::PARAM_RESPONSE_ID],
                    $this->response_link,
                    $this->params[self::PARAM_IGNORE_TOPICS]);
        } else {
            $this->response_link = $this->page_link;
        }

        // Events einlesen
        $events = $this->getEvents($month, $year,
                $this->params[self::PARAM_GROUP]);

        // Parameter fuer die Navigation
        if (($month - 1) == 0) {
            $prev_month = 12;
            $prev_year = $year - 1;
        } else {
            $prev_month = $month - 1;
            $prev_year = $year;
        }
        if (($month + 1) == 13) {
            $next_month = 1;
            $next_year = $year + 1;
        } else {
            $next_month = $month + 1;
            $next_year = $year;
        }
        // navigation
        $navigation = array(
                'prev_link' => sprintf('%s?%s=%s&%s=%s&%s=%s', $this->page_link,
                        self::REQUEST_ACTION, self::ACTION_SHOW_MONTH,
                        self::REQUEST_MONTH, $prev_month, self::REQUEST_YEAR,
                        $prev_year),
                'month_year' => sprintf('%s %d', $month_name, $year),
                'month_link' => sprintf('%s?%s=%s&%s=%s&%s=%s',
                        $this->response_link, self::REQUEST_EVENT,
                        self::EVENT_MONTH, self::REQUEST_MONTH, $month,
                        self::REQUEST_YEAR, $year),
                'next_link' => sprintf('%s?%s=%s&%s=%s&%s=%s', $this->page_link,
                        self::REQUEST_ACTION, self::ACTION_SHOW_MONTH,
                        self::REQUEST_MONTH, $next_month, self::REQUEST_YEAR,
                        $next_year)
        );

        $head = array(
                '0' => $this->getDayOfWeekName(0, 2, true),
                '1' => $this->getDayOfWeekName(1, 2, true),
                '2' => $this->getDayOfWeekName(2, 2, true),
                '3' => $this->getDayOfWeekName(3, 2, true),
                '4' => $this->getDayOfWeekName(4, 2, true),
                '5' => $this->getDayOfWeekName(5, 2, true),
                '6' => $this->getDayOfWeekName(6, 2, true)
        );

        // step through the month...
        $start_day_of_week = date('w', mktime(0, 0, 0, $month, 1, $year));
        $start = true;
        $i = 1;
        $dow = 1;
        $week = array();
        $week['week'] = date('W', mktime(0, 0, 0, $month, $i, $year));
        $complete = false;

        // should indicate the actual day?
        $check_today = ($this->params[self::PARAM_SHOW_TODAY] &&
                 (mktime(0, 0, 0, $month, 1, $year) ==
                 mktime(0, 0, 0, date('n'), 1, date('Y')))) ? true : false;

        $mon = array();
        while ($i < 50) {
            // Woche schreiben
            if (! $start && ($dow == 1)) {
                $mon[] = $week;
                if ($complete)
                    break;
                $week = array();
                $week['week'] = date('W', mktime(0, 0, 0, $month, $i, $year));
            }
            // Beim Start bis zum richtigen Wochentag durchlaufen
            if ($start) {
                if ($start_day_of_week == $dow) {
                    $start = false;
                } else {
                    if ($this->params[self::PARAM_INACTIVE_DAYS]) {
                        $x = $dow - ($start_day_of_week - 1);
                        $week[$dow]['date'] = date('j',
                                mktime(0, 0, 0, $month, $x, $year));
                        $week[$dow]['type'] = 'cms_day_inactive';
                    } else {
                        $week[$dow]['date'] = '';
                        $week[$dow]['type'] = 'cms_day_hidden';
                    }
                    $dow ++;
                    if ($dow > 6)
                        $dow = 0;
                    continue;
                }
            }
            // job is done, add the remaining cells to the row
            if (! $complete) {
                if (in_array($i, $events)) {
                    // es gibt eine oder mehrere Veranstaltungen
                    $week[$dow]['date'] = $i;
                    $week[$dow]['link'] = sprintf('%s?%s=%s&%s=%s&%s=%s&%s=%s',
                            $this->response_link, self::REQUEST_EVENT,
                            self::EVENT_DAY, self::REQUEST_MONTH, $month,
                            self::REQUEST_DAY, $i, self::REQUEST_YEAR, $year);
                    $week[$dow]['type'] = 'cms_day_event';
                } else {
                    // normaler Tag
                    $week[$dow]['date'] = $i;
                    $week[$dow]['type'] = ($check_today && ($i == date('j'))) ? 'cms_day_today' : '';
                }
            } elseif ($this->params[self::PARAM_INACTIVE_DAYS]) {
                $week[$dow]['date'] = date('j',
                        mktime(0, 0, 0, $month, $i, $year));
                $week[$dow]['type'] = 'cms_day_inactive';
            } else {
                $week[$dow]['date'] = '';
            }
            $i ++;
            if ($i > $last_day_of_month)
                $complete = true;
            $dow ++;
            if ($dow > 6)
                $dow = 0;
        }

        // show complete calendar sheet
        $data = array(
                'show_weeks' => ($this->params[self::PARAM_SHOW_WEEKS]) ? 1 : 0,
                'show_navigation' => ($this->params[self::PARAM_NAVIGATION]) ? 1 : 0,
                'link_month' => (int) $this->params[self::PARAM_LINK_MONTH],
                'navigation' => $navigation,
                'head' => $head,
                'month' => $mon
        );
        return $this->getTemplate('calendar.dwoo', $data);
    } // showCalendar()

    private function getMonthName ($month, $length = -1, $uppercase = false)
    {
        $month_names = explode(',', CFG_MONTH_NAMES);
        if (isset($month_names[$month - 1])) {
            $month_name = trim($month_names[$month - 1]);
            if ($length > 0)
                $month_name = substr($month_name, 0, $length);
            if ($uppercase)
                $month_name = strtoupper($month_name);
            return $month_name;
        } else {
            $this->setError(
                    $this->lang->translate(
                            'Error: No entry for the month number {{ month }}!',
                            array(
                                    'month' => $month
                            )));
            return false;
        }
    } // getMonthName()

    private function getDayOfWeekName ($day_of_week, $length = -1,
            $uppercase = false)
    {
        $day_names = explode(',', CFG_DAY_NAMES);
        if (isset($day_names[$day_of_week])) {
            $day_name = trim($day_names[$day_of_week]);
            if ($length > 0)
                $day_name = substr($day_name, 0, $length);
            if ($uppercase)
                $day_name = strtoupper($day_name);
            return $day_name;
        } else {
            $this->setError(
                    $this->lang->translate(
                            'Error: No entry for the day of the week {{ day_of_week }}!',
                            array(
                                    'day_of_week' => $day_of_week
                            )));
            return false;
        }
    } // getDayName()

    /**
     * Sanitize variables and prepare them for saving in a MySQL record
     *
     * @param mixed $item
     * @return mixed
     */
    public static function sanitizeVariable ($item)
    {
        if (! is_array($item)) {
            // undoing 'magic_quotes_gpc = On' directive
            if (get_magic_quotes_gpc())
                $item = stripcslashes($item);
            $item = self::sanitizeText($item);
        }
        return $item;
    } // sanitizeVariable()

    /**
     * Sanitize a text variable and prepare ist for saving in a MySQL record
     *
     * @param string $text
     * @return string
     */
    protected static function sanitizeText ($text)
    {
        $text = str_replace(
                array(
                        "<",
                        ">",
                        "\"",
                        "'"
                ),
                array(
                        "&lt;",
                        "&gt;",
                        "&quot;",
                        "&#039;"
                ), $text);
        $text = mysql_real_escape_string($text);
        return $text;
    } // sanitizeText()

    /**
     * Unsanitize a text variable and prepare it for output
     *
     * @param string $text
     * @return string
     */
    public static function unsanitizeText ($text)
    {
        $text = stripcslashes($text);
        $text = str_replace(
                array(
                        "&lt;",
                        "&gt;",
                        "&quot;",
                        "&#039;"
                ),
                array(
                        "<",
                        ">",
                        "\"",
                        "'"
                ), $text);
        return $text;
    } // unsanitizeText()

    public function showList ()
    {
        global $kitLibrary;
        global $dbEvent;
        global $dbEventItem;
        global $dbEventGroup;
        global $manufakturConfig;

        if (($this->params[self::PARAM_SELECT_MONTH] > 0) &&
                 ($this->params[self::PARAM_SELECT_MONTH] < 12)) {
            $month = $this->params[self::PARAM_SELECT_MONTH];
        } elseif ($this->params[self::PARAM_SELECT_MONTH] < 0) {
            $month = date('n') + $this->params[self::PARAM_SELECT_MONTH];
        } elseif (($this->params[self::PARAM_SELECT_MONTH] > 100) &&
                 ($this->params[self::PARAM_SELECT_MONTH] < 112)) {
            $month = date('n') + ($this->params[self::PARAM_SELECT_MONTH] - 100);
        } else {
            $month = date('n');
        }

        if ($this->params[self::PARAM_SELECT_YEAR] == 0) {
            // 0 == use actual year
            $year = date('Y');
        } elseif ($this->params[self::PARAM_SELECT_YEAR] < 0) {
            // substract value from actual year
            $year = date('Y') + $this->params[self::PARAM_SELECT_YEAR];
        } elseif (($this->params[self::PARAM_SELECT_YEAR] > 0) &&
                 ($this->params[self::PARAM_SELECT_YEAR] < 100)) {
            $year = date('Y') + $this->params[self::PARAM_SELECT_YEAR];
        } else {
            $year = $this->params[self::PARAM_SELECT_YEAR];
        }

        if ($this->params[self::PARAM_RESPONSE_ID] > 0) {
            $kitLibrary->getUrlByPageID($this->params[self::PARAM_RESPONSE_ID],
                    $this->response_link,
                    $this->params[self::PARAM_IGNORE_TOPICS]);
        } else {
            $this->response_link = $this->page_link;
        }

        // Events einlesen
        $events = $this->getEvents($month, $year,
                $this->params[self::PARAM_GROUP], false);

        $items = array();
        foreach ($events as $event) {
            $SQL = sprintf("SELECT * FROM %s WHERE %s='%s'",
                    $dbEventItem->getTableName(), dbEventItem::field_id,
                    $event[dbEvent::field_event_item]);
            $eventItem = array();
            if (! $dbEventItem->sqlExec($SQL, $eventItem)) {
                $this->setError($dbEventItem->getError());
                return false;
            }
            if (count($eventItem) < 0) {
                $this->setError(
                        $this->lang->translate(
                                'Error: The id {{ id }} is invalid!',
                                array(
                                        'id' => $event[dbEvent::field_event_item]
                                )));
                return false;
            }
            $eventItem = $eventItem[0];

            $SQL = sprintf("SELECT * FROM %s WHERE %s='%s'",
                    $dbEventGroup->getTableName(), dbEventGroup::field_id,
                    $event[dbEvent::field_event_group]);
            $eventGroup = array();
            if (! $dbEventGroup->sqlExec($SQL, $eventGroup)) {
                $this->setError($dbEventGroup->getError());
                return false;
            }
            $eventGroup = (count($eventGroup) > 0) ? $eventGroup[0] : $dbEventGroup->getFields();
            $eItem = array(
                    'headline' => self::unsanitizeText(
                            $eventItem[dbEventItem::field_title]),
                    'desc_short' => self::unsanitizeText(
                            $eventItem[dbEventItem::field_desc_short]),
                    'desc_long' => self::unsanitizeText(
                            $eventItem[dbEventItem::field_desc_long]),
                    'desc_link' => $eventItem[dbEventItem::field_desc_link],
                    'location' => self::unsanitizeText(
                            $eventItem[dbEventItem::field_location]),
                    'costs' => number_format(
                            $eventItem[dbEventItem::field_costs], 2,
                            CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR),
                    'free_field' => array(
                            1 => array(
                                    'label' => $manufakturConfig->getValue(
                                            'cfg_event_free_field_1',
                                            'kit_event'),
                                    'value' => self::unsanitizeText(
                                            $eventItem[dbEventItem::field_free_1])
                            ),
                            2 => array(
                                    'label' => $manufakturConfig->getValue(
                                            'cfg_event_free_field_2',
                                            'kit_event'),
                                    'value' => self::unsanitizeText(
                                            $eventItem[dbEventItem::field_free_2])
                            ),
                            3 => array(
                                    'label' => $manufakturConfig->getValue(
                                            'cfg_event_free_field_3',
                                            'kit_event'),
                                    'value' => self::unsanitizeText(
                                            $eventItem[dbEventItem::field_free_3])
                            ),
                            4 => array(
                                    'label' => $manufakturConfig->getValue(
                                            'cfg_event_free_field_4',
                                            'kit_event'),
                                    'value' => self::unsanitizeText(
                                            $eventItem[dbEventItem::field_free_4])
                            ),
                            5 => array(
                                    'label' => $manufakturConfig->getValue(
                                            'cfg_event_free_field_5',
                                            'kit_event'),
                                    'value' => self::unsanitizeText(
                                            $eventItem[dbEventItem::field_free_5])
                            )
                    )
            );

            $start_date = strtotime($event[dbEvent::field_event_date_from]);
            $end_date = strtotime($event[dbEvent::field_event_date_to]);
            $day_names = explode(',', CFG_DAY_NAMES);
            $month_names = explode(',', CFG_MONTH_NAMES);
            $items[] = array(
                    'event' => $eItem,
                    'start_day' => date('j', $start_date),
                    'start_day_zero' => date('d', $start_date),
                    'start_day_name' => $day_names[date('w', $start_date)],
                    'start_day_name_2' => substr($day_names[date('w', $start_date)], 0, 2),
                    'start_month' => date('n', $start_date),
                    'start_month_zero' => date('m', $start_date),
                    'start_month_name' => $month_names[date('n', $start_date) - 1],
                    'start_month_name_3' => substr($month_names[date('n', $start_date) - 1], 0, 3),
                    'start_year' => date('Y', $start_date),
                    'start_time' => date(CFG_TIME_STR, $start_date),

                    'end_day' => date('j', $end_date),
                    'end_day_zero' => date('d', $end_date),
                    'end_day_name' => $day_names[date('w', $end_date)],
                    'end_day_name_2' => substr($day_names[date('w', $end_date)], 0, 2),
                    'end_month' => date('n', $end_date),
                    'end_month_zero' => date('m', $end_date),
                    'end_month_name' => $month_names[date('n', $end_date) - 1],
                    'end_month_name_3' => substr($month_names[date('n', $end_date) - 1], 0, 3),
                    'end_year' => date('Y', $end_date),
                    'end_time' => date(CFG_TIME_STR, $end_date),

                    'participants_max' => $event[dbEvent::field_participants_max],
                    'participants_total' => $event[dbEvent::field_participants_total],
                    'participants_free' => $event[dbEvent::field_participants_max] -
                             $event[dbEvent::field_participants_total],

                            'deadline' => date(CFG_DATE_STR,
                                    strtotime($event[dbEvent::field_deadline])),

                            'group_name' => $eventGroup[dbEventGroup::field_name],
                            'group_desc' => $eventGroup[dbEventGroup::field_desc],

                            'link_order' => sprintf('%s?%s=%s&%s=%s',
                                    $this->response_link, self::REQUEST_ACTION,
                                    self::ACTION_ORDER, self::REQUEST_EVENT_ID,
                                    $event[dbEvent::field_id]),
                            'link_day' => sprintf('%s?%s=%s&%s=%s&%s=%s&%s=%s',
                                    $this->response_link, self::REQUEST_EVENT,
                                    self::EVENT_DAY, self::REQUEST_MONTH, $month,
                                    self::REQUEST_DAY, date('j', $start_date),
                                    self::REQUEST_YEAR, $year)
            );
        }

        $data = array(
                'dates' => $items
        );
        return $this->getTemplate('calendar.list.dwoo', $data);
    } // showList()

} // class monthlyCalendar