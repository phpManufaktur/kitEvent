<?php

/**
 * kitEvent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
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
    while (($level < 10) && (!file_exists($root . '/framework/class.secure.php'))) {
        $root .= $oneback;
        $level += 1;
    }
    if (file_exists($root . '/framework/class.secure.php')) {
        include ($root . '/framework/class.secure.php');
    } else {
        trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
    }
}
// end include class.secure.php

require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/initialize.php');
require_once (WB_PATH . '/include/captcha/captcha.php');
require_once (WB_PATH . '/framework/class.wb.php');
require_once (WB_PATH . '/modules/droplets_extension/interface.php');

require_once (WB_PATH.'/modules/kit/class.interface.php');
global $kitContactInterface;

require_once LEPTON_PATH . '/modules/manufaktur_config/library.php';
global $manufakturConfig;
if (!is_object($manufakturConfig))
    $manufakturConfig = new manufakturConfig('kit_event');

require_once WB_PATH.'/framework/functions-utf8.php';

class eventFrontend
{
    const REQUEST_ACTION = 'kea';
    const REQUEST_EVENT = 'evt';
    const REQUEST_YEAR = 'y';
    const REQUEST_MONTH = 'm';
    const REQUEST_DAY = 'd';
    const REQUEST_EVENT_ID = 'id';
    const REQUEST_EVENT_DETAIL = 'det';
    const REQUEST_FREE_FIELDS = 'ff';
    const REQUEST_PERMA_LINK = 'perl';
    const REQUEST_MUST_FIELDS = 'mf';
    const REQUEST_TITLE = 'title';
    const REQUEST_FIRST_NAME = 'fn';
    const REQUEST_LAST_NAME = 'ln';
    const REQUEST_COMPANY = 'com';
    const REQUEST_STREET = 'str';
    const REQUEST_ZIP = 'zip';
    const REQUEST_CITY = 'cty';
    const REQUEST_EMAIL = 'eml';
    const REQUEST_PHONE = 'phn';
    const REQUEST_BEST_TIME = 'bt';
    const REQUEST_CONFIRM = 'con';
    const REQUEST_TERMS = 'trm';
    const REQUEST_PRIVACY = 'prv';
    const REQUEST_MESSAGE = 'msg';
    const REQUEST_CAPTCHA = 'cpt';

    const ACTION_DEFAULT = 'def';
    const ACTION_DAY = 'day';
    const ACTION_EVENT = 'evt';
    const ACTION_ORDER = 'ord';
    const ACTION_ORDER_CHECK = 'chk';

    const PARAM_CATEGORY = 'category';
    const PARAM_CITY = 'city';
    const PARAM_COUNTRY = 'country';
    const PARAM_CSS = 'css';
    const PARAM_DATE = 'date';
    const PARAM_DEBUG = 'debug';
    const PARAM_DETAIL = 'detail';
    const PARAM_EVENT_ID = 'event_id';
    const PARAM_GROUP = 'group';
    const PARAM_HEADER = 'header';
    const PARAM_IGNORE_TOPICS = 'ignore_topics';
    const PARAM_LIMIT = 'limit';
    const PARAM_MODE = 'mode';
    const PARAM_MONTH = 'month';
    const PARAM_ORDER_BY = 'order_by';
    const PARAM_PRESET = 'preset';
    const PARAM_REGION = 'region';
    const PARAM_SEARCH = 'search';
    const PARAM_SORT = 'sort';
    const PARAM_VIEW = 'view';
    const PARAM_YEAR = 'year';
    const PARAM_ZIP = 'zip';

    const VIEW_ID = 'id';
    const VIEW_DAY = 'day';
    const VIEW_WEEK = 'week';
    const VIEW_MONTH = 'month';
    const VIEW_ACTIVE = 'active';
    const VIEW_FILTER = 'filter';
    const VIEW_SHEET = 'sheet';

    const MODE_MONTH = 'month';
    const MODE_WEEK = 'week';

    private $params = array(
        self::PARAM_VIEW => self::VIEW_ACTIVE,
        self::PARAM_PRESET => 1,
        self::PARAM_DETAIL => false,
        self::PARAM_GROUP => '',
        self::PARAM_EVENT_ID => -1,
        // self::PARAM_RESPONSE_ID => -1, // inactive - not used!
        self::PARAM_IGNORE_TOPICS => false,
        self::PARAM_SEARCH => false,
        self::PARAM_HEADER => false,
        self::PARAM_CSS => true,
        self::PARAM_DEBUG => false,
        self::PARAM_COUNTRY => '',
        self::PARAM_CITY => '',
        self::PARAM_ZIP => '',
        self::PARAM_ORDER_BY => '',
        self::PARAM_SORT => 'ASC',
        self::PARAM_CATEGORY => '',
        self::PARAM_DATE => '',
        self::PARAM_MONTH => '',
        self::PARAM_YEAR => '',
        self::PARAM_REGION => '',
        self::PARAM_LIMIT => '',
        self::PARAM_MODE => '',
        );

    private static $template_path;
    private static $page_link;
    private static $error;
    private static $message;

    // configuration values
    protected static $cfgICalDir = null;
    protected static $cfgICalCreate = null;
    protected static $cfgPermaLinkCreate = null;
    protected static $cfgQRCodeDir = null;
    protected static $cfgQRCodeCreate = null;
    protected static $cfgQRCodeContent = null;
    protected static $cfgFreeFieldLabel_1 = null;
    protected static $cfgFreeFieldLabel_2 = null;
    protected static $cfgFreeFieldLabel_3 = null;
    protected static $cfgFreeFieldLabel_4 = null;
    protected static $cfgFreeFieldLabel_5 = null;

    protected $lang = null;


    public function __construct()  {
        global $kitEventTools;
        global $manufakturConfig;
        global $I18n;

        $url = '';
        $_SESSION['FRONTEND'] = true;
        $kitEventTools->getPageLinkByPageID(PAGE_ID, $url);
        self::$page_link = $url;
        self::$template_path = WB_PATH . '/modules/kit_event/templates/frontend/presets/';
        date_default_timezone_set(CFG_TIME_ZONE);

        $this->lang = $I18n;

        // get the configuration values
        self::$cfgICalDir = $manufakturConfig->getValue('cfg_event_ical_directory', 'kit_event');
        self::$cfgICalCreate = $manufakturConfig->getValue('cfg_event_ical_create', 'kit_event');
        self::$cfgPermaLinkCreate = $manufakturConfig->getValue('cfg_event_perma_link_create', 'kit_event');
        self::$cfgQRCodeDir = $manufakturConfig->getValue('cfg_event_qr_code_directory', 'kit_event');
        self::$cfgQRCodeCreate = $manufakturConfig->getValue('cfg_event_qr_code_create', 'kit_event');
        self::$cfgQRCodeContent = $manufakturConfig->getValue('cfg_event_qr_code_content', 'kit_event');
        self::$cfgFreeFieldLabel_1 = $manufakturConfig->getValue('cfg_event_free_field_1', 'kit_event');
        self::$cfgFreeFieldLabel_2 = $manufakturConfig->getValue('cfg_event_free_field_2', 'kit_event');
        self::$cfgFreeFieldLabel_3 = $manufakturConfig->getValue('cfg_event_free_field_3', 'kit_event');
        self::$cfgFreeFieldLabel_4 = $manufakturConfig->getValue('cfg_event_free_field_4', 'kit_event');
        self::$cfgFreeFieldLabel_5 = $manufakturConfig->getValue('cfg_event_free_field_5', 'kit_event');
    } // __construct();

    public function getParams()
    {
        return $this->params;
    } // getParams()

    public function setParams($params = array())
    {
        $this->params = $params;
        return true;
    } // setParams()

    /**
     * Set self::$error to $error
     *
     * @param STR $error
     */
    public function setError($error)
    {
        $debug = debug_backtrace();
        $caller = next($debug);
        self::$error = sprintf('<div class="evt_error">[%s::%s - %s] %s</div>', basename($caller['file']), $caller['function'], $caller['line'], $error);
    } // setError()

    /**
     * Get Error from $this->error;
     *
     * @return STR $this->error
     */
    public function getError()
    {
        return self::$error;
    } // getError()

    /**
     * Check if $this->error is empty
     *
     * @return BOOL
     */
    public function isError()
    {
        return (bool) !empty(self::$error);
    } // isError

    /**
     * Reset Error to empty String
     */
    public function clearError()
    {
        self::$error = '';
    }

    /**
     * Set $this->message to $message
     *
     * @param STR $message
     */
    public function setMessage($message)
    {
        self::$message = sprintf('<div class="evt_message">%s</div>', $message);
    } // setMessage()

    /**
     * Get Message from $this->message;
     *
     * @return STR $this->message
     */
    public function getMessage()
    {
        return self::$message;
    } // getMessage()

    /**
     * Check if $this->message is empty
     *
     * @return BOOL
     */
    public function isMessage()
    {
        return (bool) !empty(self::$message);
    } // isMessage

    /**
     * Return Version of Module
     *
     * @return FLOAT
     */
    public function getVersion()
    {
        // read info.php into array
        $info_text = file(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.php');
        if ($info_text == false) {
            return -1;
        }
        // walk through array
        foreach ($info_text as $item) {
            if (strpos($item, '$module_version') !== false) {
                // split string $module_version
                $value = explode('=', $item);
                // return floatval
                return floatval(preg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
            }
        }
        return -1;
    } // getVersion()

    /**
     * Verhindert XSS Cross Site Scripting
     *
     * @param REFERENCE $_REQUEST
     *            Array
     * @return $request
     */
    public function xssPrevent(&$request)
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
    protected function getTemplate($template, $template_data)
    {
        global $parser;

        $template_path = self::$template_path . $this->params[self::PARAM_PRESET] . '/' . LANGUAGE . '/' . $template;
        if (!file_exists($template_path)) {
            // template does not exist - fallback to default language!
            $template_path = self::$template_path . $this->params[self::PARAM_PRESET] . '/DE/' . $template;
            if (!file_exists($template_path)) {
                // template does not exists - fallback to the default preset!
                $template_path = self::$template_path . '1/' . LANGUAGE . '/' . $template;
                if (!file_exists($template_path)) {
                    // template does not exists - fallback to the default preset
                    // and the default language
                    $template_path = self::$template_path . '1/DE/' . $template;
                    if (!file_exists($template_path)) {
                        // template does not exists in any possible path - give
                        // up!
                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error: The template {{ template }} does not exists in any of the possible paths!', array(
                            'template',
                            $template))));
                        return false;
                    }
                }
            }
        }

        // add the template_path to the $template_data (for debugging purposes)
        if (!isset($template_data['template_path']))
            $template_data['template_path'] = $template_path;
            // add the debug flag to the $template_data
        if (!isset($template_data['DEBUG']))
            $template_data['DEBUG'] = (int) $this->params[self::PARAM_DEBUG];

        try {
            // try to execute the template with Dwoo
            $result = $parser->get($template_path, $template_data);
        } catch (Exception $e) {
            // prompt the Dwoo error
            $this->setError('DWOO ERROR');
            // $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
            // $this->lang->translate('Error executing template <b>{{ template
            // }}</b>:<br />{{ error }}', array(
            // 'template' => $template,
            // 'error' => $e->getMessage()))));
            return false;
        }
        return $result;
    } // getTemplate()

    /**
     * Action Handler der class.frontend.php
     * Diese Funktion wird generell von aussen aufgerufen und steuert die
     * Klasse.
     *
     * @return STR result dialog
     */
    public function action() {
      if ($this->isError())
        return $this->getError();

      // we can ignore calls by DropletsExtions...
      if (isset($_SESSION['DROPLET_EXECUTED_BY_DROPLETS_EXTENSION'])) return '- passed call by DropletsExtension -';

      $html_allowed = array();
      foreach ($_REQUEST as $key => $value) {
        if (stripos($key, 'amp;') == 0) {
          // fix the problem, that the server does not proper rewrite &amp; to &
          $key = substr($key, 4);
          $_REQUEST[$key] = $value;
          unset($_REQUEST['amp;' . $key]);
        }
        if (!in_array($key, $html_allowed))
          $_REQUEST[$key] = $this->xssPrevent($value);
      }

      if ((isset($_REQUEST[self::REQUEST_PERMA_LINK]) && is_numeric($_REQUEST[self::REQUEST_PERMA_LINK])) || ($this->params[self::PARAM_EVENT_ID] !== -1)) {
        $_REQUEST[self::REQUEST_ACTION] = self::ACTION_EVENT;
        $_REQUEST[self::REQUEST_EVENT] = self::VIEW_ID;
        $_REQUEST[self::REQUEST_EVENT_ID] = (isset($_REQUEST[self::REQUEST_PERMA_LINK]) && is_numeric($_REQUEST[self::REQUEST_PERMA_LINK])) ? $_REQUEST[self::REQUEST_PERMA_LINK] : $this->params[self::PARAM_EVENT_ID];
        $_REQUEST[self::REQUEST_EVENT_DETAIL] = (isset($_REQUEST[self::REQUEST_EVENT_DETAIL])) ? $_REQUEST[self::REQUEST_EVENT_DETAIL] : $this->params[self::PARAM_DETAIL];
      }

      isset($_REQUEST[self::REQUEST_ACTION]) ? $action = $_REQUEST[self::REQUEST_ACTION] : $action = self::ACTION_DEFAULT;

      if (isset($_REQUEST[self::REQUEST_EVENT]))
        $action = self::ACTION_EVENT;

      switch ($action) :
        case self::ACTION_ORDER:
          $result = $this->orderEvent();
          break;
        case self::ACTION_ORDER_CHECK:
          $result = $this->checkOrder();
          break;
        case self::ACTION_EVENT:
          $result = $this->showEvent();
          break;
        default:
          $result = $this->showEvent($this->params[self::PARAM_VIEW]);
          break;
      endswitch;

      if ($this->isError())
        $result = $this->getError();
      return $result;
    } // action
    public function getMondayOfWeekDate($date)
    {
        $dow = date('w', $date);
        if ($dow == 0)
            $dow = 7;
        $sub = $dow - 1;
        return mktime(0, 0, 0, date('n', $date), date('j', $date) - $sub, date('Y', $date));
    }
    private function getStartEndDates($event_data = array(), $is_start = true)
    {
        $date = ($is_start) ? strtotime($event_data['evt_event_date_from']) : strtotime($event_data['evt_event_date_to']);
        $publish = ($is_start) ? strtotime($event_data['evt_publish_date_from']) : strtotime($event_data['evt_publish_date_to']);

        $weekdays = explode(',', CFG_DAY_NAMES);
        $months = explode(',', CFG_MONTH_NAMES);

        $dates = array(
            'timestamp' => $date,
            'date' => date(CFG_DATE_STR, $date),
            'datetime' => date(CFG_DATETIME_STR, $date),
            'time' => date(CFG_TIME_STR, $date),
            'day' => date('j', $date),
            'day_zero' => date('d', $date),
            'day_name' => trim($weekdays[date('w', $date)]),
            'day_name_2' => substr(trim($weekdays[date('w', $date)]), 0, 2),
            'month' => date('n', $date),
            'month_zero' => date('m', $date),
            'month_name' => trim($months[date('n', $date) - 1]),
            'month_name_3' => substr(trim($months[date('n', $date) - 1]), 0, 3),
            'year' => date('Y', $date),
            'year_2' => date('y', $date),
            'week' => date('W', $date),
            'publish_date' => date(CFG_DATE_STR, $publish),
            'publish_timestamp' => $publish,
            'iso' => date('c', $date));
        return $dates;
    } // getStartEndDates()

    /**
     * Sanitize variables and prepare them for saving in a MySQL record
     *
     * @param mixed $item
     * @return mixed
     */
    public static function sanitizeVariable($item)
    {
        if (!is_array($item)) {
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
    protected static function sanitizeText($text)
    {
        $text = str_replace(array(
            "<",
            ">",
            "\"",
            "'"), array(
            "&lt;",
            "&gt;",
            "&quot;",
            "&#039;"), $text);
        $text = mysql_real_escape_string($text);
        return $text;
    } // sanitizeText()

    /**
     * Unsanitize a text variable and prepare it for output
     *
     * @param string $text
     * @return string
     */
    public static function unsanitizeText($text)
    {
        $text = stripcslashes($text);
        $text = str_replace(array("&lt;","&gt;","&quot;","&#039;"), array("<",">","\"","'"), $text);
        return $text;
    } // unsanitizeText()

    /**
     * Daten fuer die angegebene Event ID auslesen und zusaetzlich ein Array mit
     * Informationen fuer die Ausgabe ueber beliebige Templates erzeugen
     *
     * @param integer $event_id
     * @param reference array $event_data
     * @param reference array $parser_data
     * @return boolean true on success
     */
    public function getEventData($event_id, &$event_data = array(), &$event_parser = array())
    {
        global $kitEventTools;
        global $database;
        global $kitContactInterface;

        $tke = TABLE_PREFIX.'mod_kit_event';
        $tkei = TABLE_PREFIX.'mod_kit_event_item';
        $SQL = "SELECT * FROM `$tke`, `$tkei` WHERE $tke.item_id=$tkei.item_id AND $tke.evt_id='$event_id'";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }
        if ($query->numRows() < 1) {
          $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id)));
          return false;
        }
        $event_data = $query->fetchRow(MYSQL_ASSOC);

        $SQL = "SELECT `group_id`,`group_name`, `group_desc` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='{$event_data['group_id']}'";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }
        if ($query->numRows() == 1) {
          $group = $query->fetchRow(MYSQL_ASSOC);
        }
        else {
          $group = array(
              'group_id' => -1,
              'group_name' => '',
              'group_desc' => ''
              );
        }

        if ($event_data['evt_participants_max'] > 0) {
            $participants_max = $event_data['evt_participants_max'];
            $participants_free = (($x = $event_data['evt_participants_max'] - $event_data['evt_participants_total']) > 0) ? $x : $this->lang->translate('- out of stock -');
        }
        else {
            $participants_max = $this->lang->translate('- unlimited -');
            $participants_free = $this->lang->translate('- places available -');
        }

        $start = strtotime($event_data['evt_event_date_from']);
        $end = strtotime($event_data['evt_event_date_to']);

        // QR Code
        if (self::$cfgQRCodeCreate) {
            // QR Code verwenden
            $dir = $kitEventTools->removeLeadingSlash(self::$cfgQRCodeDir);
            $dir = $kitEventTools->addSlash($dir);
            $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
            $filename = $event_data['evt_qrcode_image'];
            if (!empty($filename) && file_exists($dir_path . $filename)) {
                list ($qrcode_width, $qrcode_height) = getimagesize($dir_path . $filename);
                $qrcode_src = WB_URL . MEDIA_DIRECTORY . '/' . $dir . $filename;
                $qrcode_type = self::$cfgQRCodeContent;
                $qrcode_text = ($qrcode_type == 1) ? $this->lang->translate('The QR-Code contains a link to this event') : $this->lang->translate('The QR-Code contains iCal informations');
            } else {
                $qrcode_src = '';
                $qrcode_width = 0;
                $qrcode_height = 0;
                $qrcode_text = '';
                $qrcode_type = 0;
            }
        } else {
            $qrcode_src = '';
            $qrcode_width = 0;
            $qrcode_height = 0;
            $qrcode_text = '';
            $qrcode_type = 0;
        }
        // iCal
        if (self::$cfgICalCreate && !empty($event_data['evt_ical_file'])) {
            $dir = $kitEventTools->removeLeadingSlash(self::$cfgICalDir);
            $dir = $kitEventTools->addSlash($dir);
            $dir_url = WB_URL . MEDIA_DIRECTORY . '/' . $dir;
            $filename = $event_data['evt_ical_file'];
            $ical_link = $dir_url . $filename;
        } else {
            $ical_link = '';
        }

        // KIT contact record for the Location
        $location_contact = array();
        if (!$kitContactInterface->getContact($event_data['location_id'], $location_contact)) {
//          $this->setError($kitContactInterface->getError());
//          return false;
        }
        // KIT contact record for the organizer
        $organizer_contact = array();
        if (!$kitContactInterface->getContact($event_data['organizer_id'], $organizer_contact)) {
//          $this->setError($kitContactInterface->getError());
//          return false;
        }

        $event_parser = array(
            'headline' => $event_data['item_title'],
            'id' => $event_data['evt_id'],
            'group' => array(
                'id' => $group['group_id'],
                'name' => $group['group_name'],
                'description' => $group['group_desc']),
            'start' => $this->getStartEndDates($event_data, true),
            'end' => $this->getStartEndDates($event_data, false),
            'participants' => array(
                'max' => $participants_max,
                'total' => $event_data['evt_participants_total'],
                'free' => $participants_free),
            'deadline' => array(
                'date' => strtotime($event_data['evt_deadline'])),
            'description' => array(
                'short' => self::unsanitizeText($event_data['item_desc_short']),
                'long' => self::unsanitizeText($event_data['item_desc_long'])),
            'free_field' => array(
                1 => array(
                    'label' => self::$cfgFreeFieldLabel_1,
                    'value' => self::unsanitizeText($event_data['item_free_1'])),
                2 => array(
                    'label' => self::$cfgFreeFieldLabel_2,
                    'value' => self::unsanitizeText($event_data['item_free_2'])),
                3 => array(
                    'label' => self::$cfgFreeFieldLabel_3,
                    'value' => self::unsanitizeText($event_data['item_free_3'])),
                4 => array(
                    'label' => self::$cfgFreeFieldLabel_4,
                    'value' => self::unsanitizeText($event_data['item_free_4'])),
                5 => array(
                    'label' => self::$cfgFreeFieldLabel_5,
                    'value' => self::unsanitizeText($event_data['item_free_5']))
                ),
            'location' => $event_data['item_location'],
            'costs' => array(
                'value' => $event_data['item_costs'],
                'format' => array(
                    'float' => number_format($event_data['item_costs'], 2, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR),
                    'currency' => sprintf(CFG_CURRENCY, number_format($event_data['item_costs'], 2, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR)))),
            'link' => array(
                'description' => self::unsanitizeText($event_data['item_desc_link']),
                'register' => sprintf('%s%s%s',
                    self::$page_link,
                    (strpos(self::$page_link, '?') === false) ? '?' : '&',
                    http_build_query(array(
                      self::REQUEST_ACTION => self::ACTION_ORDER,
                      self::REQUEST_EVENT_ID => $event_id
                        ))
                    ),
                'detail' => sprintf('%s%s%s',
                    self::$page_link,
                    (strpos(self::$page_link, '?') === false) ? '?' : '&',
                    http_build_query(array(
                      self::REQUEST_ACTION => self::ACTION_EVENT,
                      self::REQUEST_EVENT_ID => $event_id,
                      self::REQUEST_EVENT => self::VIEW_ID,
                      self::REQUEST_EVENT_DETAIL => 1
                        ))
                    ),
                'start' => self::$page_link,
                'permanent' => (empty($event_data['evt_perma_link'])) ? '' : WB_URL . PAGES_DIRECTORY . '/' . $event_data['evt_perma_link'],
                'ical' => $ical_link
                ),
            'qr_code' => array(
                'is_active' => (int) self::$cfgQRCodeCreate,
                'image' => array(
                    'src' => $qrcode_src,
                    'width' => $qrcode_width,
                    'height' => $qrcode_height,
                    'text' => $qrcode_text,
                    'type' => $qrcode_type
                    )
                ),
            // release 0.39 introduce the extra fields
            'extra' => array(
                'location' => array(
                    'id' => $event_data['location_id'],
                    'contact' => $location_contact,
                    'alias' => $event_data['item_location'],
                    'category' => self::unsanitizeText($event_data['item_category']),
                    'link' => $event_data['item_location_link']
                    ),
                'organizer' => array(
                    'id' => $event_data['organizer_id'],
                    'contact' => $organizer_contact
                    )
                )
            );
        return true;
    } // getEventData()

    /**
     * Anmeldung zu einem Event pruefen, ggf. wieder Anmeldedialog mit Hinweisen
     * anzeigen. Wenn OK, Daten uebernehmen, Zaehler aktualisieren und E-Mails
     * an Besteller sowie an den Seitenbetreiber versenden
     *
     * @return FALSE on error or DIALOG/MESSAGE on success
     */
    public function checkOrder() {
        global $kitEventTools;
        global $wb;
        global $database;
        global $kitContactInterface;

        if (!isset($_REQUEST[self::REQUEST_EVENT_ID]) && !isset($_REQUEST['evt_id'])) {
            $this->setError($this->lang->translate('Error: This event is invalid!'));
            return false;
        }
        $event_id = (isset($_REQUEST[self::REQUEST_EVENT_ID])) ? (int) $_REQUEST[self::REQUEST_EVENT_ID] : (int) $_REQUEST['evt_id'];

        if (!isset($_REQUEST[self::REQUEST_MUST_FIELDS])) {
            $this->setError($this->lang->translate('Error: The must fields for the form are not defined!'));
            return false;
        }
        $mf = strtolower($_REQUEST[self::REQUEST_MUST_FIELDS]);
        $mf = str_replace(' ', '', $mf);
        $must_fields = explode(',', $mf);
        if (!in_array(self::REQUEST_EMAIL, $must_fields))
            $must_fields[] = self::REQUEST_EMAIL;
        $message = '';
        foreach ($must_fields as $must_field) {
            switch ($must_field) :
                case self::REQUEST_CAPTCHA:
                    if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha']))
                        $message .= $this->lang->translate('<p>The CAPTCHA is invalid!</p>');
                    break;
                case self::REQUEST_CITY:
                    if (!isset($_REQUEST[self::REQUEST_CITY]) || (strlen($_REQUEST[self::REQUEST_CITY]) < 4))
                        $message .= $this->lang->translate('<p>Please type in the city!</p>');
                    break;
                case self::REQUEST_EMAIL:
                    if (!isset($_REQUEST[self::REQUEST_EMAIL]) || !$kitEventTools->validateEMail($_REQUEST[self::REQUEST_EMAIL])) {
                        $message .= $this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid!</p>', array(
                            'email' => $_REQUEST[self::REQUEST_EMAIL]));
                    }
                    break;
                case self::REQUEST_FIRST_NAME:
                    if (!isset($_REQUEST[self::REQUEST_FIRST_NAME]) || empty($_REQUEST[self::REQUEST_FIRST_NAME]))
                        $message .= $this->lang->translate('<p>Please type in your first name!</p>');
                    break;
                case self::REQUEST_LAST_NAME:
                    if (!isset($_REQUEST[self::REQUEST_LAST_NAME]) || empty($_REQUEST[self::REQUEST_LAST_NAME]))
                        $message .= $this->lang->translate('<p>Please type in your last name!</p>');
                    break;
                case self::REQUEST_PHONE:
                    if (!isset($_REQUEST[self::REQUEST_PHONE]) || empty($_REQUEST[self::REQUEST_PHONE]))
                        $message .= $this->lang->translate('<p>Please type in your phone number!</p>');
                    break;
                case self::REQUEST_STREET:
                    if (!isset($_REQUEST[self::REQUEST_STREET]) || empty($_REQUEST[self::REQUEST_STREET]))
                        $message .= $this->lang->translate('<p>Please type in your street!</p>');
                    break;
                case self::REQUEST_TERMS:
                    if (!isset($_REQUEST[self::REQUEST_TERMS]))
                        $message .= $this->lang->translate('<p>Please accept our terms and conditions!</p>');
                    break;
                case self::REQUEST_PRIVACY:
                    if (!isset($_REQUEST[self::REQUEST_PRIVACY]))
                        $message .= $this->lang->translate('<p>Please accept our data privacy!</p>');
                    break;
                case self::REQUEST_ZIP:
                    if (!isset($_REQUEST[self::REQUEST_ZIP]) || empty($_REQUEST[self::REQUEST_ZIP]))
                        $message .= $this->lang->translate('<p>Please type in your ZIP code!</p>');
                    break;
            endswitch
            ;
        }
        if (!empty($message)) {
            $this->setMessage($message);
            return $this->orderEvent();
        }

        // ok - Daten sichern und Bestaetigungsmails versenden
        $contact = array(
            'kit_city' => (isset($_REQUEST[self::REQUEST_CITY])) ? $_REQUEST[self::REQUEST_CITY] : '',
            'kit_company' => (isset($_REQUEST[self::REQUEST_COMPANY])) ? $_REQUEST[self::REQUEST_COMPANY] : '',
            'kit_email' => strtolower($_REQUEST[self::REQUEST_EMAIL]),
            'kit_first_name' => (isset($_REQUEST[self::REQUEST_FIRST_NAME])) ? $_REQUEST[self::REQUEST_FIRST_NAME] : '',
            'kit_last_name' => (isset($_REQUEST[self::REQUEST_LAST_NAME])) ? $_REQUEST[self::REQUEST_LAST_NAME] : '',
            'kit_phone' => (isset($_REQUEST[self::REQUEST_PHONE])) ? $_REQUEST[self::REQUEST_PHONE] : '',
            'kit_street' => (isset($_REQUEST[self::REQUEST_STREET])) ? $_REQUEST[self::REQUEST_STREET] : '',
            'kit_title' => (isset($_REQUEST[self::REQUEST_TITLE])) ? $_REQUEST[self::REQUEST_TITLE] : '',
            'kit_zip' => (isset($_REQUEST[self::REQUEST_ZIP])) ? $_REQUEST[self::REQUEST_ZIP] : ''
            );
        $kit_id = -1;
        $registry = array();
        if ($kitContactInterface->isEMailRegistered($contact['kit_email'], $kit_id)) {
          if (!$kitContactInterface->updateContact($kit_id, $contact)) {
            $this->setError($kitContactInterface->getError());
            return false;
          }
        }
        elseif (!$kitContactInterface->addContact($contact, $kit_id)) {
          $this->setError($kitContactInterface->getError());
          return false;
        }

        $free_fields = (isset($_REQUEST[self::REQUEST_FREE_FIELDS])) ? explode(',', $_REQUEST[self::REQUEST_FREE_FIELDS]) : array();

        $orderData = array(
            'kit_id' => $kit_id,
            'ord_best_time' => (isset($_REQUEST[self::REQUEST_BEST_TIME])) ? $_REQUEST[self::REQUEST_BEST_TIME] : '',
            'ord_confirm' => (isset($_REQUEST[self::REQUEST_CONFIRM])) ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00',
            'evt_id' => $event_id,
            'ord_message' => (isset($_REQUEST[self::REQUEST_MESSAGE])) ? $_REQUEST[self::REQUEST_MESSAGE] : '',
            'ord_date' => date('Y-m-d H:i:s'),
            'ord_free_1' => (isset($_REQUEST['ord_free_1'])) ? $_REQUEST['ord_free_1'] : '',
            'ord_free_2' => (isset($_REQUEST['ord_free_2'])) ? $_REQUEST['ord_free_2'] : '',
            'ord_free_3' => (isset($_REQUEST['ord_free_3'])) ? $_REQUEST['ord_free_3'] : '',
            'ord_free_4' => (isset($_REQUEST['ord_free_4'])) ? $_REQUEST['ord_free_4'] : '',
            'ord_free_5' => (isset($_REQUEST['ord_free_5'])) ? $_REQUEST['ord_free_5'] : ''
            );

        $fields = '';
        $values = '';
        $start = true;
        foreach ($orderData as $field => $value) {
          $fields .= (!$start) ? ",`$field`" : "`$field`";
          $values .= (!$start) ? ",'$value'" : "'$value'";
          $start = false;
        }
        $SQL = "INSERT INTO `".TABLE_PREFIX."mod_kit_event_order` ($fields) VALUES ($values)";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }
        $order_id = mysql_insert_id();

        // wenn eine Anmeldung erfolgt ist, muss der Zaehler bei dbEvent erhoeht werden!
        if (false !== ($dt = $orderData['ord_confirm'])) {
            $SQL = "SELECT `evt_participants_total` FROM `".TABLE_PREFIX."mod_kit_event` WHERE `evt_id`='$event_id'";
            if (null === ($query = $database->query($SQL))) {
              $this->setError($database->get_error());
              return false;
            }
            if ($query->numRows() < 1) {
              $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id)));
              return false;
            }
            $counter = $query->fetchRow(MYSQL_ASSOC);
            $total = $counter['evt_participants_total']+1;

            $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_participants_total`='$total' WHERE `evt_id`='$event_id'";
            if (null === $database->query($SQL)) {
              $this->setError($database->get_error());
              return false;
            }
        }

        // Bestaetigungsmail an den Kunden
        $order = array(
            'title' => $contact['kit_title'],
            'first_name' => $contact['kit_first_name'],
            'last_name' => $contact['kit_last_name'],
            'company' => $contact['kit_company'],
            'street' => $contact['kit_street'],
            'zip' => $contact['kit_zip'],
            'city' => $contact['kit_city'],
            'email' => $contact['kit_email'],
            'phone' => $contact['kit_phone'],
            'best_time' => $orderData['ord_best_time'],
            'message' => $orderData['ord_message'],
            'confirm_datetime' => (!strtotime($orderData['ord_confirm'])) ? NULL : date(CFG_DATETIME_STR, strtotime($orderData['ord_confirm'])),
            'confirm_timestamp' => (!strtotime($orderData['ord_confirm'])) ? NULL : strtotime($orderData['ord_confirm']),
            'free_1' => $orderData['ord_free_1'],
            'free_2' => $orderData['ord_free_2'],
            'free_3' => $orderData['ord_free_3'],
            'free_4' => $orderData['ord_free_4'],
            'free_5' => $orderData['ord_free_5']
        );

        $event = array();
        $event_parser = array();
        if (!$this->getEventData($event_id, $event, $event_parser))
            return false;
        $data = array(
            'contact' => $contact,
            'order' => $order,
            'event' => $event_parser);

        if (false == ($body = $this->getTemplate('mail.confirm.participant.dwoo', $data)))
            return false;
        if (!$wb->mail(SERVER_EMAIL, $contact['kit_email'], $event['item_title'], $body)) {
            $this->setError($this->lang->translate('Error: cannot send the email to {{ email }}!', array(
                'email' => $contact['kit_email'])));
            return false;
        }

        // Datensatz aktualisieren
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event_order` SET `ord_send_mail`='".date('Y-m-d H:i:s')."' WHERE `ord_id`='$order_id'";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }

        // E-Mail an Seitenbetreiber
        if (false == ($body = $this->getTemplate('mail.confirm.admin.dwoo', $data)))
            return false;
        if (!$wb->mail(SERVER_EMAIL, SERVER_EMAIL, $event['item_title'], $body)) {
            $this->setError($this->lang->translate('Error: cannot send the email to {{ email }}!', array(
                'email' => SERVER_EMAIL)));
            return false;
        }

        return $this->getTemplate('frontend.event.order.confirm.dwoo', $data);
    } // checkOrder()

    /**
     * Bestell- und Kontaktdialog fuer die Events
     *
     * @return STR dialog
     */
    public function orderEvent()
    {

        if (!isset($_REQUEST[self::REQUEST_EVENT_ID]) && !isset($_REQUEST['evt_id'])) {
            $this->setError($this->lang->translate('Error: This event is invalid!'));
            return false;
        }
        $event_id = (isset($_REQUEST[self::REQUEST_EVENT_ID])) ? (int) $_REQUEST[self::REQUEST_EVENT_ID] : (int) $_REQUEST['evt_id'];

        $event = array();
        $parser_data = array();
        if (!$this->getEventData($event_id, $event, $parser_data))
            return false;

        $event_group = $event['group_id'];

        $request = array();
        // persoenliche Anrede...
        $titles = array(
            'Mister',
            'Lady');
        $options = '';
        $title_values = array();
        foreach ($titles as $title) {
            $title_values[] = array(
                'value' => $title,
                'text' => $title,
                'selected' => (isset($_REQUEST[self::REQUEST_TITLE]) && ($_REQUEST[self::REQUEST_TITLE] == $title)) ? 1 : NULL);
        }
        $request['title']['name'] = self::REQUEST_TITLE;
        $request['title']['value'] = $title_values;

        // Eingabefelder erzeugen
        $input_array = array(
            'first_name' => self::REQUEST_FIRST_NAME,
            'last_name' => self::REQUEST_LAST_NAME,
            'company' => self::REQUEST_COMPANY,
            'street' => self::REQUEST_STREET,
            'zip' => self::REQUEST_ZIP,
            'city' => self::REQUEST_CITY,
            'email' => self::REQUEST_EMAIL,
            'phone' => self::REQUEST_PHONE,
            'best_time' => self::REQUEST_BEST_TIME,
            'message' => self::REQUEST_MESSAGE,
            'confirm_order' => self::REQUEST_CONFIRM,
            'confirm_terms' => self::REQUEST_TERMS,
            'confirm_privacy' => self::REQUEST_PRIVACY,
            'free_1' => 'ord_free_1',
            'free_2' => 'ord_free_2',
            'free_3' => 'ord_free_3',
            'free_4' => 'ord_free_4',
            'free_5' => 'ord_free_5');
        foreach ($input_array as $field => $name) {
            $request[$field]['name'] = $name;
            $request[$field]['value'] = (isset($_REQUEST[$name])) ? $_REQUEST[$name] : NULL;
        }
        // CAPTCHA
        ob_start();
        call_captcha();
        $call_captcha = ob_get_contents();
        ob_end_clean();
        $request['captcha']['name'] = self::REQUEST_CAPTCHA;
        $request['captcha']['print'] = $call_captcha;
        $data = array(
            'form_name' => 'event_order',
            'form_action' => self::$page_link,
            'action_name' => self::REQUEST_ACTION,
            'action_value' => self::ACTION_ORDER_CHECK,
            'event_name' => 'evt_id',
            'event_value' => $event_id,
            'must_fields_name' => self::REQUEST_MUST_FIELDS,
            'define_free_fields' => self::REQUEST_FREE_FIELDS,
            'event' => $parser_data,
            'response' => ($this->isMessage()) ? $this->getMessage() : NULL,
            'request' => $request);

        return $this->getTemplate('frontend.event.order.dwoo', $data);
    } // orderEvent()


    public function showEvent($show_view = -1) {
        if (!isset($_REQUEST[self::REQUEST_EVENT]) && ($show_view == -1)) {
            $this->setError($this->lang->translate('Error: This event is invalid!'));
            return false;
        }
        $event_view = (isset($_REQUEST[self::REQUEST_EVENT])) ? strtolower(trim($_REQUEST[self::REQUEST_EVENT])) : $show_view;
        $event_view = trim(strtolower($event_view));

        // Register Droplet for the WebsiteBaker Search Function
        if (function_exists('is_registered_droplet_search') && ($this->params[self::PARAM_SEARCH] && !is_registered_droplet_search('kit_event', PAGE_ID))) {
            register_droplet_search('kit_event', PAGE_ID, 'kit_event');
        }
        if (function_exists('is_registered_droplet_header') && ($this->params[self::PARAM_HEADER] && !is_registered_droplet_header('kit_event', PAGE_ID))) {
            register_droplet_header('kit_event', PAGE_ID, 'kit_event');
        }
        if (function_exists('is_registered_droplet_css') && ($this->params[self::PARAM_CSS] && !is_registered_droplet_css('kit_event', PAGE_ID))) {
            register_droplet_css('kit_event', PAGE_ID, 'kit_event', 'kit_event.css');
        }

        switch ($event_view) :
            case self::VIEW_ID:
                $result = $this->viewEventID();
                break;
            case self::VIEW_DAY:
                $result = $this->viewEventDay();
                break;
            case self::VIEW_MONTH:
                $result = $this->viewEventMonth();
                break;
            case self::VIEW_WEEK:
                $result = $this->viewEventWeek();
                break;
            case self::VIEW_ACTIVE:
                $result = $this->viewEventActive();
                break;
            case self::VIEW_FILTER:
              $result = $this->viewEventFilter();
              break;
            case self::VIEW_SHEET:
                $result = $this->viewSheet();
                break;
            default:
                // nicht spezifiziertes Event
                $this->setError($this->lang->translate('Error: The event view <b>{{ view }}</b> is not specified!', array(
                    'view' => $event_view)));
                return false;
        endswitch
        ;
        return $result;
    }

    public function viewEventID($event_id = -1, $show_details = true)
    {
        $show_details = (isset($_REQUEST[self::REQUEST_EVENT_DETAIL])) ? (bool) $_REQUEST[self::REQUEST_EVENT_DETAIL] : $show_details;
        $event_id = (isset($_REQUEST[self::REQUEST_EVENT_ID])) ? (int) $_REQUEST[self::REQUEST_EVENT_ID] : $event_id;
        $parser_data = array();
        $event_data = array();
        if (!$this->getEventData($event_id, $event_data, $parser_data))
            return false;
        $data = array(
            'show_details' => ($show_details) ? 1 : 0,
            'event' => $parser_data);
        return $this->getTemplate('frontend.view.id.dwoo', $data);
    } // viewEventID()

    /**
     * Enter description here ...
     */
    public function viewEventDay()
    {
        global $database;

        if (!isset($_REQUEST[self::REQUEST_DAY]) || !isset($_REQUEST[self::REQUEST_MONTH]) || !isset($_REQUEST[self::REQUEST_YEAR])) {
            // keine Parameter gesetzt - aktuelles Datum verwenden!
            $month = date('n');
            $day = date('j');
            $year = date('Y');
        } else {
            $month = (int) $_REQUEST[self::REQUEST_MONTH];
            $day = (int) $_REQUEST[self::REQUEST_DAY];
            $year = (int) $_REQUEST[self::REQUEST_YEAR];
        }

        $search_date_from = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day - 1, $year));
        $search_date_to = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $day + 1, $year));
        $dt = mktime(0, 0, 0, $month, $day, $year);

        $weekdays = explode(',', CFG_DAY_NAMES);
        $months = explode(',', CFG_MONTH_NAMES);

        $day = array(
            'date' => date(CFG_DATE_STR, $dt),
            'day' => date('j', $dt),
            'day_zero' => date('d', $dt),
            'day_name' => trim($weekdays[date('w', $dt)]),
            'day_name_2' => substr(trim($weekdays[date('w', $dt)]), 1, 2),
            'month_name' => trim($months[date('n', $dt) - 1]),
            'month_name_3' => substr(trim($months[date('n', $dt) - 1]), 1, 3),
            'month' => date('n', $dt),
            'month_zero' => date('m', $dt),
            'year' => date('Y', $dt),
            'year_2' => date('y', $dt),
            'week' => date('W', $dt),
            'link_start' => self::$page_link);

        $filter_group = '';
        $group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
        if (!empty($group)) {
            $SQL = "SELECT `group_id` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_name`='$group' AND `group_status`='1'";
            if (null === ($query = $database->query($SQL))) {
              $this->setError($database->get_error());
              return false;
            }
            if ($query->numRows() < 1) {
              $this->setError($this->lang->translate('Error: The group {{ group }} does not exists, please check the params!', array(
                  'group' => $group)));
              return false;
            }
            $grp = $query->fetchRow(MYSQL_ASSOC);
            $filter_group = " AND `group_id`='{$grp['group_id']}'";
        }

        $SQL = "SELECT `evt_id` FROM `".TABLE_PREFIX."mod_kit_event` WHERE ".
          "(`evt_event_date_from` BETWEEN '$search_date_from' AND '$search_date_to') AND `evt_status`='1'$filter_group";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }

        $event_items = array();
        while (false !== ($event = $query->fetchRow(MYSQL_ASSOC))) {
            $event_data = array();
            $parser_data = array();
            if (!$this->getEventData($event['evt_id'], $event_data, $parser_data))
                return false;
            $event_items[] = $parser_data;
        }
        $show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
        $data = array(
            'day' => $day,
            'show_details' => ($show_details) ? 1 : 0,
            'events' => (count($event_items) > 0) ? $event_items : null
            );
        return $this->getTemplate('frontend.view.day.dwoo', $data);
    } // viewEventDay()

    public function viewEventMonth()
    {
        global $database;

        if (!isset($_REQUEST[self::REQUEST_MONTH]) || !isset($_REQUEST[self::REQUEST_YEAR])) {
            // keine Parameter gesetzt - aktuelles Datum verwenden!
            $month = date('n');
            $year = date('Y');
        } else {
            $month = (int) $_REQUEST[self::REQUEST_MONTH];
            $year = (int) $_REQUEST[self::REQUEST_YEAR];
        }
        $search_date_from = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, 0, $year));
        $search_date_to = date('Y-m-d H:i:s', mktime(0, 0, 0, $month + 1, 1, $year));
        $dt = mktime(0, 0, 0, $month, 1, $year);
        $months = explode(',', CFG_MONTH_NAMES);

        if ($month == 1) {
            $prev_month = 11;
            $prev_year = $year - 1;
        }
        else {
            $prev_month = $month - 2;
            $prev_year = $year;
        }
        if ($month == 12) {
            $next_month = 0;
            $next_year = $year + 1;
        }
        else {
            $next_month = $month;
            $next_year = $year;
        }
        $data_month = array(
            'month' => $month,
            'month_zero' => date('m', $dt),
            'month_name' => $months[$month - 1],
            'month_name_3' => substr($months[$month - 1], 1, 3),
            'year' => $year,
            'year_2' => date('y', $dt),
            'last_day' => date('j', mktime(0, 0, 0, $month + 1, 0, $year)),
            'prev_month' => $prev_month + 1,
            'prev_month_zero' => sprintf('%02d', $prev_month + 1),
            'prev_month_name' => $months[$prev_month],
            'prev_month_name_3' => substr($months[$prev_month], 1, 3),
            'next_month' => $next_month,
            'next_month_zero' => sprintf('%02d', $next_month),
            'next_month_name' => $months[$next_month],
            'next_month_name_3' => substr($months[$next_month], 1, 3),
            'link_start' => self::$page_link,
            'link_prev_month' => sprintf('%s?%s',
                self::$page_link,
                http_build_query(array(
                    self::REQUEST_ACTION => self::ACTION_EVENT,
                    self::REQUEST_EVENT => self::VIEW_MONTH,
                    self::REQUEST_MONTH => $prev_month+1,
                    self::REQUEST_YEAR => $prev_year
                    ))
                ),
            'link_next_month' => sprintf('%s?%s=%s&%s=%s&%s=%s&%s=%s',
                self::$page_link,
                http_build_query(array(
                    self::REQUEST_ACTION => self::ACTION_EVENT,
                    self::REQUEST_EVENT => self::VIEW_MONTH,
                    self::REQUEST_MONTH => $next_month+1,
                    self::REQUEST_YEAR => $next_year
                    ))
                )
            );
        $filter_group = '';
        $group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
        if (!empty($group)) {
            $SQL = "SELECT `group_id` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_name`='$group' AND `group_status`='1'";
            if (null === ($query = $database->query($SQL))) {
              $this->setError($database->get_error());
              return false;
            }
            if ($query->numRows() < 1) {
              $this->setError($this->lang->translate('Error: The group {{ group }} does not exists, please check the params!', array(
                  'group' => $group)));
              return false;
            }
            $grp = $query->fetchRow(MYSQL_ASSOC);
            $filter_group = " AND `group_id`='{$grp['group_id']}'";
        }

        $SQL = "SELECT `evt_id` FROM `".TABLE_PREFIX."mod_kit_event` WHERE ".
          "(`evt_event_date_from` BETWEEN '$search_date_from' AND '$search_date_to') ".
          "AND `evt_status`='1'$filter_group ORDER BY `evt_event_date_from` ASC";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }

        $event_items = array();
        while (false !== ($event = $query->fetchRow(MYSQL_ASSOC))) {
            $event_data = array();
            $parser_data = array();
            if (!$this->getEventData($event['evt_id'], $event_data, $parser_data))
                return false;
            $event_items[] = $parser_data;
        }
        $show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
        $data = array(
            'show_details' => ($show_details) ? 1 : 0,
            'month' => $data_month,
            'events' => (count($event_items) > 0) ? $event_items : NULL);
        return $this->getTemplate('frontend.view.month.dwoo', $data);
    } // viewEventMonth()

    public function viewEventWeek()
    {
        global $database;

        if (!isset($_REQUEST[self::REQUEST_DAY]) || !isset($_REQUEST[self::REQUEST_MONTH]) || !isset($_REQUEST[self::REQUEST_YEAR])) {
            // keine Parameter gesetzt - aktuelles Datum verwenden!
            $month = date('n');
            $day = date('j');
            $year = date('Y');
        } else {
            $month = (int) $_REQUEST[self::REQUEST_MONTH];
            $day = (int) $_REQUEST[self::REQUEST_DAY];
            $year = (int) $_REQUEST[self::REQUEST_YEAR];
        }
        $start = $this->getMondayOfWeekDate(mktime(0, 0, 0, $month, $day, $year));
        $monday = date('j', $start);
        $day = date('j', $start);
        $month = date('n', $start);
        $year = date('Y', $start);

        $search_date_from = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $monday - 1, $year));
        $search_date_to = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $monday + 7, $year));
        $dt = mktime(0, 0, 0, $month, $monday, $year);
        $months = explode(',', CFG_MONTH_NAMES);

        $prev_date = mktime(0, 0, 0, $month, $monday - 7, $year);
        $next_date = mktime(0, 0, 0, $month, $monday + 7, $year);

        $week = array(
            'monday' => date('j', $dt),
            'monday_zero' => date('d', $dt),
            'sunday' => date('j', mktime(0, 0, 0, $month, $monday + 6, $year)),
            'sunday_zero' => date('d', mktime(0, 0, 0, $month, $monday + 6, $year)),
            'week' => (int) date('W', $dt),
            'week_zero' => date('W', $dt),
            'year' => date('Y', $dt),
            'year_2' => date('y', $dt),
            'month' => date('n', $dt),
            'month_zero' => date('m', $dt),
            'month_name' => $months[date('n') - 1],
            'month_name_3' => substr($months[date('n') - 1], 1, 3),
            'link_prev_week' => sprintf('%s?%s',
                self::$page_link,
                http_build_query(array(
                    self::REQUEST_ACTION => self::ACTION_EVENT,
                    self::REQUEST_EVENT => self::VIEW_WEEK,
                    self::REQUEST_MONTH => date('n', $prev_date),
                    self::REQUEST_DAY => date('j', $prev_date),
                    self::REQUEST_YEAR => date('Y', $prev_date)
                    ))
                ),
            'link_next_week' => sprintf('%s?%s',
                self::$page_link,
                http_build_query(array(
                    self::REQUEST_ACTION => self::ACTION_EVENT,
                    self::REQUEST_EVENT => self::VIEW_WEEK,
                    self::REQUEST_MONTH => date('n', $next_date),
                    self::REQUEST_DAY => date('j', $next_date),
                    self::REQUEST_YEAR => date('Y', $next_date)
                    ))
                ),
            'link_start' => self::$page_link
            );

        $filter_group = '';
        $group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
        if (!empty($group)) {
          $SQL = "SELECT `group_id` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_name`='$group' AND `group_status`='1'";
          if (null === ($query = $database->query($SQL))) {
            $this->setError($database->get_error());
            return false;
          }
          if ($query->numRows() < 1) {
            $this->setError($this->lang->translate('Error: The group {{ group }} does not exists, please check the params!', array(
                'group' => $group)));
            return false;
          }
          $grp = $query->fetchRow(MYSQL_ASSOC);
          $filter_group = " AND `group_id`='{$grp['group_id']}'";
        }

        $SQL = "SELECT `evt_id` FROM `".TABLE_PREFIX."mod_kit_event` WHERE ".
            "(`evt_event_date_from` BETWEEN '$search_date_from' AND '$search_date_to') ".
            "AND `evt_status`='1'$filter_group ORDER BY `evt_event_date_from` ASC";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }

        $event_items = array();
        while (false !== ($event = $query->fetchRow(MYSQL_ASSOC))) {
            $event_data = array();
            $parser_data = array();
            if (!$this->getEventData($event['evt_id'], $event_data, $parser_data))
                return false;
            $event_items[] = $parser_data;
        }
        $show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
        $data = array(
            'show_details' => ($show_details) ? 1 : 0,
            'events' => (count($event_items) > 0) ? $event_items : NULL,
            'week' => $week);
        return $this->getTemplate('frontend.view.week.dwoo', $data);
    } // viewEventWeek()

    public function viewEventActive()
    {
        global $database;

        $search_date_from = date('Y-m-d H:i:s', mktime(23, 59, 59, date('n'), date('j') - 1, date('Y')));
        $search_date_to = date('Y-m-d H:i:s', mktime(23, 59, 59, date('n'), date('j'), date('Y')));
        $months = explode(',', CFG_MONTH_NAMES);

        $filter_group = '';
        $group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
        if (!empty($group)) {
          $SQL = "SELECT `group_id` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_name`='$group' AND `group_status`='1'";
          if (null === ($query = $database->query($SQL))) {
            $this->setError($database->get_error());
            return false;
          }
          if ($query->numRows() < 1) {
            $this->setError($this->lang->translate('Error: The group {{ group }} does not exists, please check the params!', array(
                'group' => $group)));
            return false;
          }
          $grp = $query->fetchRow(MYSQL_ASSOC);
          $filter_group = " AND `group_id`='{$grp['group_id']}'";
        }

        $SQL = "SELECT `evt_id` FROM `".TABLE_PREFIX."mod_kit_event` WHERE ".
            "(`evt_publish_date_from` <= '$search_date_from' AND `evt_publish_date_to` >= '$search_date_to') ".
            "AND `evt_status`='1'$filter_group ORDER BY `evt_event_date_from` ASC";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }

        if ($query->numRows() < 1) {
          $this->setMessage($this->lang->translate('<p>There are no events for {{ date }}!</p>',
              array('date' => $months[date('n') - 1])));
          return $this->getMessage();
        }
        $event_items = array();
        while (false !== ($event = $query->fetchRow(MYSQL_ASSOC))) {
            $event_data = array();
            $parser_data = array();
            if (!$this->getEventData($event['evt_id'], $event_data, $parser_data))
                return false;
            $event_items[$event['evt_id']] = $parser_data;
        }
        $show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
        $data = array(
            'events' => $event_items,
            'show_details' => ($show_details) ? 1 : 0,
            'module' => array(
                'directory' => WB_URL.'/modules/kit_event',
                'path' => WB_PATH.'/modules/kit_event'
                )
            );
        return $this->getTemplate('frontend.view.active.dwoo', $data);
    } // viewEventActive


    private function viewEventFilter() {
      global $database;

      $evt = TABLE_PREFIX.'mod_kit_event';
      $its = TABLE_PREFIX.'mod_kit_event_item';
      $kit = TABLE_PREFIX.'mod_kit_contact';
      $addr = TABLE_PREFIX.'mod_kit_contact_address';

      // Basic SQL string to get all events
      $SQL = "SELECT `evt_id`, `address_country`, `address_zip`, `address_city` FROM `$evt`, `$kit`, `$addr`, `$its` ".
        "WHERE location_id=$kit.contact_id AND $evt.item_id=$its.item_id AND contact_address_standard=address_id AND evt_status='1'";

      if (!empty($this->params[self::PARAM_COUNTRY])) {
        // filter the country
        $countries = explode(',', strtoupper($this->params[self::PARAM_COUNTRY]));
        $add = '';
        foreach ($countries as $country) {
          $country = trim($country);
          $country = entities_to_umlauts2($country);
          if (!empty($add)) $add .= " OR ";
          $add .= "`address_country`='$country'";
        }
        $SQL .= (count($countries) > 1) ? " AND ($add)" : " AND $add";
      }

      if (!empty($this->params[self::PARAM_CITY])) {
        // filter the city
        $cities = explode(',', $this->params[self::PARAM_CITY]);
        $add = '';
        foreach ($cities as $city) {
          $city = trim($city);
          $city = entities_to_umlauts2($city);
          if (!empty($add)) $add .= " OR ";
          $add .= "`address_city`='$city'";
        }
        $SQL .= (count($cities) > 1) ? " AND ($add)" : " AND $add";
      }

      if (!empty($this->params[self::PARAM_REGION])) {
        // filter the region
        $regions = explode(',', $this->params[self::PARAM_REGION]);
        $add = '';
        foreach ($regions as $region) {
          $region = trim($region);
          $region = entities_to_umlauts2($region);
          if (!empty($add)) $add .= " OR ";
          $add .= "`address_region`='$region'";
        }
        $SQL .= (count($regions) > 1) ? " AND ($add)" : " AND $add";
      }

      if (!empty($this->params[self::PARAM_CATEGORY])) {
        // filter the category
        $categories = explode(',', $this->params[self::PARAM_CATEGORY]);
        $add = '';
        foreach ($categories as $category) {
          $category = trim($category);
          $category = entities_to_umlauts2($category);
          if (!empty($add)) $add .= " OR ";
          $add .= "`item_category`='$category'";
        }
        $SQL .= (count($categories) > 1) ? " AND ($add)" : " AND $add";
      }

      if (!empty($this->params[self::PARAM_ZIP])) {
        // filter the ZIPs in LIKE mode
        $zips = explode(',', strtolower($this->params[self::PARAM_ZIP]));
        if (in_array('zero', $zips)) {
          unset($zips[array_search('zero', $zips)]);
          $zips[] = '0';
        }
        $add = '';
        foreach ($zips as $zip) {
          $zip = trim($zip);
          if (!empty($add)) $add .= ' OR ';
          $add .= "`address_zip` LIKE '$zip%'";
        }
        $SQL .= (count($zips) > 1) ? " AND ($add)" : " AND $add";
      }

      if (!empty($this->params[self::PARAM_DATE])) {
        // filter dates
        $dates = explode(',', $this->params[self::PARAM_DATE]);
        if (count($dates) == 1) {
          // filter a day
          if (strtoupper($dates[0]) == 'TODAY') {
            // filter today
            $start = date('Y-m-d 00:00:00');
            $end = date('Y-m-d 23:59:59');
            $SQL .= " AND `evt_event_date_from` >= '$start' AND `evt_event_date_to` <= '$end'";
          }
          else {
            // filter a specific day
            $start = date('Y-m-d 00:00:00', strtotime($dates[0]));
            $end = date('Y-m-d 23:59:59', strtotime($dates[0]));
            $SQL .= " AND `evt_event_date_from` >= '$start' AND `evt_event_date_to` <= '$end'";
          }
        }
        else {
          // filter between two dates
          if ((strtoupper($dates[0]) == 'TODAY') || (strtoupper($dates[0]) == 'TODAY_PERSISTS')) {
            // filter starts TODAY!
            if (strtoupper($dates[1]) == 'ALL') {
              // all events from today on
              $start = date('Y-m-d 00:00:00');
              $SQL .= " AND (`evt_event_date_from` >= '$start'";
            }
            else {
              // we assume the second parameter tells how many days!
              $days = (int) $dates[1];
              $start = date('Y-m-d 00:00:00');
              $end = date('Y-m-d H:i:s', mktime(23, 59, 59, date('n'), date('j')+$days, date('Y')));
              $SQL .= " AND (`evt_event_date_from` >= '$start' AND `evt_event_date_from` <= '$end'";
            }
            if (strtoupper($dates[0]) == 'TODAY_PERSISTS') {
                $SQL .= " OR `evt_event_date_to` >= '$start')";
            }
            else {
                $SQL .= ')';
            }
          }
          else {
            $start = date('Y-m-d 00:00:00', strtotime($dates[0]));
            $end = date('Y-m-d 23:59:59', strtotime($dates[1]));
            $SQL .= " AND `evt_event_date_from` >= '$start' AND `evt_event_date_to` <= '$end'";
          }
        }
      }
      elseif (empty($this->params[self::PARAM_MONTH]) && empty($this->params[self::PARAM_YEAR])) {
        // set default - show only events within the publishing period
        $today = date('Y-m-d H:i:s');
        $SQL .= " AND `evt_publish_date_from` <= '$today' AND `evt_publish_date_to` >= '$today'";
      }

      if (!empty($this->params[self::PARAM_YEAR]) && empty($this->params[self::PARAM_MONTH])) {
        // filter the year (only if no month is specified)
        $year = (int) $this->params[self::PARAM_YEAR];
        if ($year < 1000) $year += 2000;
        $start = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $year));
        $end = date('Y-m-d H:i:s', mktime(23, 59, 59, 12, 31, $year));
        $SQL .= " AND `evt_event_date_from` >= '$start' AND `evt_event_date_to` <= '$end'";
      }

      if (!empty($this->params[self::PARAM_MONTH])) {
        // filter the month and the year
        $month = (int) $this->params[self::PARAM_MONTH];
        // use the actual year if not specified
        $year = (!empty($this->params[self::PARAM_YEAR])) ? (int) $this->params[self::PARAM_YEAR] : date('Y');
        $start = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 1, $year));
        $end = date('Y-m-d H:i:s', mktime(23,59,59, $month+1, 0, $year));
        $SQL .= " AND `evt_event_date_from` >= '$start' AND `evt_event_date_from` <= '$end'";
      }

      // ORDER BY must be added at the last position of the query!

      if (!empty($this->params[self::PARAM_ORDER_BY])) {
        $order_by = explode(',', strtolower($this->params[self::PARAM_ORDER_BY]));
        $add = '';
        foreach ($order_by as $field) {
          $group = trim($field);
          switch ($field):
          case 'country':
            $add .= (!empty($add)) ? ", `address_country`" : "`address_country`";
            break;
          case 'city':
            $add .= (!empty($add)) ? ", `address_city`" : "`address_city`";
            break;
          case 'zip':
            $add .= (!empty($add)) ? ", `address_zip`" : "`address_zip`";
            break;
          case 'category':
            $add .= (!empty($add)) ? ", `item_category`" : "`item_category`";
            break;
          case 'date':
            $add .= (!empty($add)) ? ", `evt_event_date_from`" : "`evt_event_date_from`";
            break;
          endswitch;
        }
        if (!empty($add))
          $SQL .= " ORDER BY $add ".$this->params[self::PARAM_SORT];
      }
      else {
        // set the default order and sort mode
        $SQL .= " ORDER BY `evt_event_date_from` ".$this->params[self::PARAM_SORT];
      }

      // but... it's possible that we have a limit!
      if (!empty($this->params[self::PARAM_LIMIT])) {
        $limit = (int) $this->params[self::PARAM_LIMIT];
        $SQL .= " LIMIT $limit";
      }

      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }

      if ($query->numRows() < 1) {
        $this->setMessage($this->lang->translate('<p>There are no events by the actual filter settings!</p>'));
        return $this->getMessage();
      }

      $event_items = array();
      while (false !== ($event = $query->fetchRow(MYSQL_ASSOC))) {
        $event_data = array();
        $parser_data = array();
        if (!$this->getEventData($event['evt_id'], $event_data, $parser_data))
          return false;
        $event_items[$event['evt_id']] = $parser_data;
      }

      $data = array(
          'events' => $event_items,
          'show_details' => (int) $this->params[self::PARAM_DETAIL],
          'module' => array(
              'directory' => WB_URL.'/modules/kit_event',
              'path' => WB_PATH.'/modules/kit_event'
          )
      );
      return $this->getTemplate('frontend.view.active.dwoo', $data);
    } // viewEventFilter();


    /**
     * Count the days between to dates and return the result
     *
     * @param datetime $from
     * @param datetime $to
     * @return number
     */
    protected function countDays($from, $to)
    {
        $first_date = strtotime($from);
        $second_date = strtotime($to);
        $offset = $second_date-$first_date;
        return floor($offset/60/60/24)+1;
    }

    /**
     * Use view=sheet to display event data for a calendar sheet
     *
     * @return string parsed sheet
     */
    protected function viewSheet()
    {
        global $database;

        $check = (!empty($this->params[self::PARAM_MONTH])) ? explode(',', $this->params[self::PARAM_MONTH]) : array(0,1);
        if (isset($check[0]) && isset($check[1])) {
            $start = (int) $check[0];
            $count = (int) $check[1];
            $first_month = date('n')+$start;
        }
        else {
            $this->setError('<p>Parameter "month" must be empty for the actual month or used as "month=start,count", example: month=0,1 show the actual month, month=0,2 show the actual month and the next, month=-1,3 show the last, the actual and the next month.</p>');
            return false;
        }

        $result = array();
        $months = array();
        for ($month=$first_month; $month < ($first_month+$count); $month++) {
            // get the days of the month
            $days_in_month = date('t', mktime(0, 0, 0, $month, 1, date('Y')));
            // get the actual month
            $actual_month = date('n', mktime(0, 0, 0, $month, 1, date('Y')));
            // get the actual year
            $actual_year = date('Y', mktime(0, 0, 0, $month, 1, date('Y')));
            // loop through the days of the month
            $table_event = TABLE_PREFIX.'mod_kit_event';
            $table_event_item = TABLE_PREFIX.'mod_kit_event_item';
            $last_ids = array(-1);
            $days = array();
            for ($day=1; $day < ($days_in_month+1); $day++) {
                $day_start = date('Y-m-d H:i:s', mktime(0, 0, 0, $actual_month, $day, $actual_year));
                $day_end =   date('Y-m-d H:i:s', mktime(23, 59, 59, $actual_month, $day, $actual_year));

                $id_check = '';
                foreach ($last_ids as $last_id) {
                    if (!empty($id_check))
                        $id_check .= ' OR ';
                    $id_check .= "$table_event.item_id = '$last_id'";
                }
                $SQL = "SELECT `evt_event_date_from`, `evt_event_date_to`, `item_title`, `item_desc_short`, $table_event.item_id FROM `$table_event`, `$table_event_item` WHERE ($table_event.item_id = $table_event_item.item_id) ".
                    "AND ((`evt_event_date_from`>='$day_start' AND `evt_event_date_from`<='$day_end') OR (($id_check) AND `evt_event_date_to` >= '$day_start')) AND `evt_status`='1' ORDER BY `evt_event_date_from` ASC";
                if (null === ($query = $database->query($SQL))) {
                    $this->setError($database->get_error());
                    return false;
                }
                $day_array = array();
                $last_ids = array(-1);
                if ($query->numRows() > 0) {
                    while (false !== ($record = $query->fetchRow(MYSQL_ASSOC))) {
                        $last_ids[] = $record['item_id'];
                        $day_array[] = array(
                            'id' => $record['item_id'],
                            'day' => $day,
                            'month' => $actual_month,
                            'year' => $actual_year,
                            'weekday' => array(
                                'id' => date('w', mktime(0, 0, 0, $actual_month, $day, $actual_year)),
                                'name' => date('l', mktime(0, 0, 0, $actual_month, $day, $actual_year)),
                                'name_2' => substr(date('l', mktime(0, 0, 0, $actual_month, $day, $actual_year)), 0, 2),
                            ),
                            'event' => array(
                                'days_total' => $this->countDays($record['evt_event_date_from'], $record['evt_event_date_to']),
                                'day_actual' => ($day - date('j', strtotime($record['evt_event_date_from'])))+1
                            ),
                            'title' => $record['item_title'],
                            'description' => array(
                                'html' => $record['item_desc_short'],
                                'text' => strip_tags($record['item_desc_short'])
                            ),
                            'link' => array(
                                'register' => sprintf('%s%s%s',
                                    self::$page_link,
                                    (strpos(self::$page_link, '?') === false) ? '?' : '&',
                                    http_build_query(array(
                                        self::REQUEST_ACTION => self::ACTION_ORDER,
                                        self::REQUEST_EVENT_ID => $record['item_id']
                                    ))
                                ),
                                'detail' => sprintf('%s%s%s',
                                    self::$page_link,
                                    (strpos(self::$page_link, '?') === false) ? '?' : '&',
                                    http_build_query(array(
                                        self::REQUEST_ACTION => self::ACTION_EVENT,
                                        self::REQUEST_EVENT_ID => $record['item_id'],
                                        self::REQUEST_EVENT => self::VIEW_ID,
                                        self::REQUEST_EVENT_DETAIL => 1
                                    ))
                                )
                            )

                        );
                    }
                }
                else {
                    // no hit, add empty record
                    $day_array[] = array(
                        'id' => -1,
                        'day' => $day,
                        'month' => $actual_month,
                        'year' => $actual_year,
                        'weekday' => array(
                            'id' => date('w', mktime(0, 0, 0, $actual_month, $day, $actual_year)),
                            'name' => date('l', mktime(0, 0, 0, $actual_month, $day, $actual_year)),
                            'name_2' => substr(date('l', mktime(0, 0, 0, $actual_month, $day, $actual_year)), 0, 2),
                        ),
                        'title' => ''
                    );
                }

                $days[$day] = $day_array;
            }
            $months[] = array(
                'number' => $actual_month,
                'name' => date('F', mktime(0, 0, 0, $actual_month, 1, $actual_year)),
                'days' => $days
                );
        }
        $result = $months;
        $data = array(
            'events' => $months,
            'max_events_per_day' => (!empty($this->params[self::PARAM_LIMIT])) ? (int) $this->params[self::PARAM_LIMIT] : 2
        );
        echo $this->params[self::PARAM_LIMIT];
        return $this->getTemplate('event.sheet.dwoo', $data);
    } // viewSheet()

} // class eventFrontend

?>