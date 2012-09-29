<?php

/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011-2012 phpManufaktur by Ralf Hertsch
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License (GPL)
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION')) include (WB_PATH . '/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root . '/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root . '/framework/class.secure.php')) {
    include ($root . '/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/initialize.php');
require_once (WB_PATH . '/modules/perma_link/class.interface.php');
require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/include/ical/iCalcreator.class.php');
require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/include/qrcode/qrlib.php');
require_once (WB_PATH . '/framework/functions-utf8.php');

require_once LEPTON_PATH.'/modules/manufaktur_config/class.dialog.php';
require_once LEPTON_PATH.'/modules/manufaktur_config/library.php';
global $manufakturConfig;
if (!is_object($manufakturConfig))
  $manufakturConfig = new manufakturConfig('kit_event');


class eventBackend {

  const REQUEST_ACTION = 'kea';
  const REQUEST_ITEMS = 'its';
  const REQUEST_TIME_START = 'ets';
  const REQUEST_TIME_END = 'ete';
  const REQUEST_SUGGESTION = 'sgg';
  const REQUEST_SHOW_ALL = 'sa';

  const ACTION_ABOUT = 'abt';
  const ACTION_CONFIG = 'cfg';
  const ACTION_DEFAULT = 'def';
  const ACTION_EDIT = 'edt';
  const ACTION_EDIT_CHECK = 'edtc';
  const ACTION_GROUP = 'grp';
  const ACTION_GROUP_CHECK = 'grpc';
  const ACTION_LIST = 'lst';
  const ACTION_MESSAGES = 'msg';
  const ACTION_MESSAGES_DETAIL = 'msgd';

  private static $tab_navigation_array = array(
    self::ACTION_LIST => 'TAB_LIST',
    self::ACTION_EDIT => 'TAB_EDIT',
    self::ACTION_GROUP => 'TAB_GROUP',
    self::ACTION_MESSAGES => 'TAB_MESSAGES',
    self::ACTION_CONFIG => 'TAB_CONFIG',
    self::ACTION_ABOUT => 'TAB_ABOUT'
  );

  private static $page_link = '';
  private static $img_url = '';
  private static $template_path = '';
  private static $error = '';
  private static $message = '';

  // configuration values
  protected static $cfgICalDir = null;
  protected static $cfgICalCreate = null;
  protected static $cfgPermaLinkCreate = null;
  protected static $cfgQRCodeDir = null;
  protected static $cfgQRCodeCreate = null;
  protected static $cfgQRCodeSize = null;
  protected static $cfgQRCodeECLevel = null;
  protected static $cfgQRCodeMargin = null;
  protected static $cfgQRCodeContent = null;

  protected $lang = null;

  public function __construct() {
    global $I18n;
    global $manufakturConfig;

    $this->lang = $I18n;
    self::$page_link = ADMIN_URL . '/admintools/tool.php?tool=kit_event';
    self::$template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/templates/backend/';
    self::$img_url = WB_URL . '/modules/' . basename(dirname(__FILE__)) . '/images/';
    date_default_timezone_set(CFG_TIME_ZONE);
    // get the configuration values
    self::$cfgICalDir = $manufakturConfig->getValue('cfg_event_ical_directory', 'kit_event');
    self::$cfgICalCreate = $manufakturConfig->getValue('cfg_event_ical_create', 'kit_event');
    self::$cfgPermaLinkCreate = $manufakturConfig->getValue('cfg_event_perma_link_create', 'kit_event');
    self::$cfgQRCodeDir = $manufakturConfig->getValue('cfg_event_qr_code_directory', 'kit_event');
    self::$cfgQRCodeCreate = $manufakturConfig->getValue('cfg_event_qr_code_create', 'kit_event');
    self::$cfgQRCodeSize = $manufakturConfig->getValue('cfg_event_qr_code_size', 'kit_event');
    self::$cfgQRCodeECLevel = $manufakturConfig->getValue('cfg_event_qr_code_ec_level', 'kit_event');
    self::$cfgQRCodeMargin = $manufakturConfig->getValue('cfg_event_qr_code_margin', 'kit_event');
    self::$cfgQRCodeContent = $manufakturConfig->getValue('cfg_event_qr_code_content', 'kit_event');
  } // __construct()

  /**
   * Set self::$error to $error
   *
   * @param STR $error
   */
  public function setError($error) {
    $caller = next(debug_backtrace());
    self::$error = sprintf('[%s::%s - %s] %s', basename($caller['file']), $caller['function'], $caller['line'], $error);
  } // setError()

  /**
   * Get Error from self::$error;
   *
   * @return STR self::$error
   */
  public function getError() {
    return self::$error;
  } // getError()

  /**
   * Check if self::$error is empty
   *
   * @return BOOL
   */
  public function isError() {
    return (bool) !empty(self::$error);
  } // isError

  /**
   * Reset Error to empty String
   */
  public function clearError() {
    self::$error = '';
  }

  /**
   * Set self::$message to $message
   *
   * @param STR $message
   */
  public function setMessage($message) {
    self::$message = $message;
  } // setMessage()

  /**
   * Get Message from self::$message;
   *
   * @return STR self::$message
   */
  public function getMessage() {
    return self::$message;
  } // getMessage()

  /**
   * Check if self::$message is empty
   *
   * @return BOOL
   */
  public function isMessage() {
    return (bool) !empty(self::$message);
  } // isMessage

  public function clearMessage() {
    self::$message = '';
  } // clearMessage()

  /**
   * Return Version of Module
   *
   * @return float
   */
  public function getVersion() {
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
   * Get the template, set the data and return the compiled result
   *
   * @param string $template the name of the template
   * @param array $template_data
   * @param boolean $trigger_error raise a trigger error on problems
   * @return boolean|Ambigous <string, mixed>
   */
  protected function getTemplate($template, $template_data, $trigger_error=false) {
  	global $parser;

  	// check if a language depending template exists
  	$template_path = (file_exists(self::$template_path.LANGUAGE.'/'.$template)) ? self::$template_path.LANGUAGE.'/' : self::$template_path.'DE/';
  	// check if a custom template exists ...
  	$load_template = (file_exists($template_path.'custom.'.$template)) ? $template_path.'custom.'.$template : $template_path.$template;
  	try {
  		$result = $parser->get($load_template, $template_data);
  	}
  	catch (Exception $e) {
  		$this->setError($this->lang->translate('Error executing the template <b>{{ template }}</b>: {{ error }}',
  		    array('template' => basename($load_template), 'error' => $e->getMessage())));
  		if ($trigger_error)
  			trigger_error($this->getError(), E_USER_ERROR);
  		return false;
  	}
  	return $result;
  } // getTemplate()


  /**
   * Verhindert XSS Cross Site Scripting
   *
   * @param Array REFERENCE $_REQUEST
   * @return $request
   */
  public function xssPrevent(&$request) {
    if (is_string($request)) {
      $request = html_entity_decode($request);
      $request = strip_tags($request);
      $request = trim($request);
      $request = stripslashes($request);
    }
    return $request;
  } // xssPrevent()

  public function action() {
    $html_allowed = array(
      dbEventItem::field_desc_long,
      dbEventItem::field_desc_short
    );
    foreach ($_REQUEST as $key => $value) {
      if (stripos($key, 'amp;') == 0) {
        // fix the problem, that the server does not proper rewrite &amp; to &
        $key = substr($key, 4);
        $_REQUEST[$key] = $value;
        unset($_REQUEST['amp;'.$key]);
      }
      if (!in_array($key, $html_allowed)) {
        $_REQUEST[$key] = $this->xssPrevent($value);
      }
    }
    isset($_REQUEST[self::REQUEST_ACTION]) ? $action = $_REQUEST[self::REQUEST_ACTION] : $action = self::ACTION_DEFAULT;
    switch ($action) :
      case self::ACTION_ABOUT :
        $this->show(self::ACTION_ABOUT, $this->dlgAbout());
        break;
      case self::ACTION_CONFIG :
        $this->show(self::ACTION_CONFIG, $this->dlgConfig());
        break;
      case self::ACTION_EDIT :
        $this->show(self::ACTION_EDIT, $this->dlgEditEvent());
        break;
      case self::ACTION_EDIT_CHECK :
        $this->show(self::ACTION_EDIT, $this->checkEditEvent());
        break;
      case self::ACTION_GROUP :
        $this->show(self::ACTION_GROUP, $this->dlgEditGroup());
        break;
      case self::ACTION_GROUP_CHECK :
        $this->show(self::ACTION_GROUP, $this->checkEditGroup());
        break;
      case self::ACTION_MESSAGES :
        $this->show(self::ACTION_MESSAGES, $this->dlgMessages());
        break;
      case self::ACTION_MESSAGES_DETAIL :
        $this->show(self::ACTION_MESSAGES, $this->dlgMessageDetail());
        break;
      case self::ACTION_LIST :
      default :
        $this->show(self::ACTION_LIST, $this->dlgList());
        break;
    endswitch
    ;
  } // action

  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   *
   * @param string $action aktives Navigationselement
   * @param string $content Inhalt
   *
   * @return ECHO RESULT
   */
  public function show($action, $content) {
    $navigation = array();
    foreach (self::$tab_navigation_array as $key => $value) {
      $navigation[] = array(
        'active' => ($key == $action) ? 1 : 0,
        'url' => sprintf('%s&%s=%s', self::$page_link, self::REQUEST_ACTION, $key),
        'text' => $value
      );
    }
    $data = array(
      'WB_URL' => WB_URL,
      'navigation' => $navigation,
      'error' => ($this->isError()) ? 1 : 0,
      'content' => ($this->isError()) ? $this->getError() : $content
    );
    echo $this->getTemplate('body.dwoo', $data);
  } // show()

  public function dlgList() {
    global $dbEvent;
    global $dbEventItem;
    global $dbEventGroup;
    global $parser;

    if (isset($_REQUEST[self::REQUEST_SHOW_ALL]) && ($_REQUEST[self::REQUEST_SHOW_ALL] == 1)) {
      $SQL = sprintf("SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s!='%s' ORDER BY %s ASC",
          TABLE_PREFIX.'mod_kit_event', //$dbEvent->getTableName(),
          TABLE_PREFIX.'mod_kit_event_item', //$dbEventItem->getTableName(),
          TABLE_PREFIX.'mod_kit_event', //$dbEvent->getTableName(),
          'item_id', //dbEvent::field_event_item,
          TABLE_PREFIX.'mod_kit_event_item', //$dbEventItem->getTableName(),
          'item_id', //dbEventItem::field_id,
          'evt_status', //dbEvent::field_status,
          '-1', //dbEvent::status_deleted,
          'evt_event_date_from' //dbEvent::field_event_date_from
          );
      $this->setMessage($this->lang->translate('<p>All events are shown!</p>'));
    }
    else {
      $start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
      $SQL = sprintf("SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s!='%s' AND %s>='%s' ORDER BY %s ASC",
          TABLE_PREFIX.'mod_kit_event', //$dbEvent->getTableName(),
          TABLE_PREFIX.'mod_kit_event_item', //$dbEventItem->getTableName(),
          TABLE_PREFIX.'mod_kit_event', //$dbEvent->getTableName(),
          'item_id', //dbEvent::field_event_item,
          TABLE_PREFIX.'mod_kit_event_item', //$dbEventItem->getTableName(),
          'item_id', //dbEventItem::field_id,
          'evt_status', //dbEvent::field_status,
          '-1', //dbEvent::status_deleted,
          'evt_event_date_from', //dbEvent::field_event_date_from,
          $start_date,
          'evt_event_date_from' //dbEvent::field_event_date_from
          );
    }
    $events = array();
    if (!$dbEvent->sqlExec($SQL, $events)) {
      $this->setError($dbEvent->getError());
      return false;
    }

    $items = '';
    $rows = array();
    foreach ($events as $event) {
      $where = array(
        dbEventGroup::field_id => $event[dbEvent::field_event_group]
      );
      $group = array();
      if (!$dbEventGroup->sqlSelectRecord($where, $group)) {
        $this->setError($dbEventGroup->getError());
        return false;
      }
      $grp = (count($group) > 0) ? $group[0][dbEventGroup::field_name] : '';

      $group = -1;
      $rows[] = array(
        'id_name' => dbEvent::field_id,
        'id_link' => sprintf('%s&%s=%s&%s=%s', self::$page_link, self::REQUEST_ACTION, self::ACTION_EDIT, dbEvent::field_id, $event[dbEvent::field_id]),
        'id' => sprintf('%04d', $event[dbEvent::field_id]),
        'date_from_name' => dbEvent::field_event_date_from,
        'date_from' => date(CFG_DATETIME_STR, strtotime($event[dbEvent::field_event_date_from])),
        'date_to_name' => dbEvent::field_event_date_to,
        'date_to' => date(CFG_DATETIME_STR, strtotime($event[dbEvent::field_event_date_to])),
        'group_name' => dbEvent::field_event_group,
        'group' => $grp,
        'part_max_name' => dbEvent::field_participants_max,
        'part_max' => $event[dbEvent::field_participants_max],
        'part_total_name' => dbEvent::field_participants_total,
        'part_total' => $event[dbEvent::field_participants_total],
        'deadline_name' => dbEvent::field_deadline,
        'deadline' => date(CFG_DATE_STR, strtotime($event[dbEvent::field_deadline])),
        'title_name' => dbEventItem::field_title,
        'title' => $event[dbEventItem::field_title]
      );
    }

    $data = array(
      'message' => array(
          'active' => (int) $this->isMessage(),
          'content' => $this->getMessage()
          ),
      'rows' => $rows,
      'show_all_link' => sprintf('%s&%s=%s&%s=1', self::$page_link, self::REQUEST_ACTION, self::ACTION_LIST, self::REQUEST_SHOW_ALL),
    );
    return $this->getTemplate('event.list.dwoo', $data);
  } // dlgList()

  /**
   * Sanitize variables and prepare them for saving in a MySQL record
   *
   * @param mixed $item
   * @return mixed
   */
  public static function sanitizeVariable($item) {
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
  protected static function sanitizeText($text) {
    $text = str_replace(array("<",">","\"","'"), array("&lt;","&gt;","&quot;","&#039;"), $text);
    $text = mysql_real_escape_string($text);
    return $text;
  } // sanitizeText()

  /**
   * Unsanitize a text variable and prepare it for output
   *
   * @param string $text
   * @return string
   */
  public static function unsanitizeText($text) {
    $text = stripcslashes($text);
    $text = str_replace(array("&lt;","&gt;","&quot;","&#039;"), array("<",">","\"","'"), $text);
    return $text;
  } // unsanitizeText()


  public function dlgSuggestEvent() {
    global $dbEvent;
    global $dbEventItem;

    $SQL = sprintf("SELECT %s.%s,%s,%s FROM %s, %s WHERE %s.%s = %s.%s AND %s!='%s' ORDER BY %s DESC", $dbEventItem->getTableName(), dbEventItem::field_id, dbEvent::field_event_date_from, dbEventItem::field_title, $dbEvent->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, dbEvent::field_status, dbEvent::status_deleted, dbEvent::field_event_date_from);
    $events = array();
    if (!$dbEvent->sqlExec($SQL, $events)) {
      $this->setError($dbEvent->getError());
      return false;
    }

    $suggest_options = array();
    $suggest_options[] = array(
      'value' => -1,
      'text' => $this->lang->translate('- do not use data from a previous event -')
    );
    foreach ($events as $event) {
      $suggest_options[] = array(
        'value' => $event[dbEventItem::field_id],
        'text' => sprintf('[ %s ] %s', date(CFG_DATE_STR, strtotime($event[dbEvent::field_event_date_from])), $event[dbEventItem::field_title])
      );
    }

    $data = array(
      'form_name' => 'event_suggest',
      'form_action' => self::$page_link,
      'action_name' => self::REQUEST_ACTION,
      'action_value' => self::ACTION_EDIT,
      'suggest_request' => self::REQUEST_SUGGESTION,
      'suggest_options' => $suggest_options,
      'abort_location' => self::$page_link
    );
    return $this->getTemplate('event.suggest.dwoo', $data);
  } // dlgEventSuggestion()

  public function dlgEditEvent() {
    global $dbEvent;
    global $dbEventGroup;
    global $dbEventItem;
    global $parser;

    $event_id = (isset($_REQUEST[dbEvent::field_id]) && ($_REQUEST[dbEvent::field_id] > 0)) ? $_REQUEST[dbEvent::field_id] : -1;
    if ($event_id !== -1) {
      $SQL = sprintf("SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s.%s='%s'", $dbEvent->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, $dbEvent->getTableName(), dbEvent::field_id, $event_id);
      $event = array();
      if (!$dbEvent->sqlExec($SQL, $event)) {
        $this->setError($dbEvent->getError());
        return false;
      }
      if (count($event) < 1) {
        $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id)));
        return false;
      }
      $event = $event[0];
      $item_id = $event[dbEvent::field_event_item];
      if ((date('H', strtotime($event[dbEvent::field_event_date_from])) !== 0) && (date('i', strtotime($event[dbEvent::field_event_date_from])) !== 0)) {
        $time_start = date(CFG_TIME_STR, strtotime($event[dbEvent::field_event_date_from]));
      }
      else {
        $time_start = '';
      }
      if ((date('H', strtotime($event[dbEvent::field_event_date_to])) !== 0) && (date('i', strtotime($event[dbEvent::field_event_date_to])) !== 0)) {
        $time_end = date(CFG_TIME_STR, strtotime($event[dbEvent::field_event_date_to]));
      }
      else {
        $time_end = '';
      }
    }
    elseif (!isset($_REQUEST[self::REQUEST_SUGGESTION])) {
      // erster Aufruf - Datenuebernahme von bestehenden Events anbieten
      return $this->dlgSuggestEvent();
    }
    elseif (isset($_REQUEST[self::REQUEST_SUGGESTION]) && ($_REQUEST[self::REQUEST_SUGGESTION] != -1)) {
      $item_id = -1;
      $event = $dbEvent->getFields();
      $event[dbEvent::field_status] = dbEvent::status_active;
      $time_start = '';
      $time_end = '';
      $where = array(
        dbEventItem::field_id => $_REQUEST[self::REQUEST_SUGGESTION]
      );
      $item = array();
      if (!$dbEventItem->sqlSelectRecord($where, $item)) {
        $this->setError($dbEventItem->getError());
        return false;
      }
      $item = $item[0];
      $event = array_merge($event, $item);
      $this->setMessage($this->lang->translate('<p>This event was taken from the previous event with the ID {{ id }}</p>',
          array('id' => $_REQUEST[self::REQUEST_SUGGESTION])));
    }
    else {
      $item_id = -1;
      $event = $dbEvent->getFields();
      $event[dbEvent::field_status] = dbEvent::status_active;
      $items = $dbEventItem->getFields();
      $event = array_merge($event, $items);
      $time_start = '';
      $time_end = '';
    }
    foreach ($event as $key => $value) {
      if (isset($_REQUEST[$key])) {
        switch ($key) :
          case dbEvent::field_event_date_from :
            if (false !== ($x = strtotime($_REQUEST[$key]))) {
              $event[$key] = date('Y-m-d H:i:s', $x);
              $time_start = ((date('H', $x) !== 0) || (date('i', $x) !== 0)) ? date(CFG_TIME_STR, $x) : '';
            }
            break;
          case dbEvent::field_event_date_to :
            if (false !== ($x = strtotime($_REQUEST[$key]))) {
              $event[$key] = date('Y-m-d H:i:s', $x);
              $time_end = ((date('H', $x) !== 0) || (date('i', $x) !== 0)) ? date(CFG_TIME_STR, $x) : '';
            }
            break;
          default :
            $event[$key] = $_REQUEST[$key];
        endswitch
        ;
      }
    }
    if (isset($_REQUEST[self::REQUEST_TIME_START])) $time_start = $_REQUEST[self::REQUEST_TIME_START];
    if (isset($_REQUEST[self::REQUEST_TIME_END])) $time_end = $_REQUEST[self::REQUEST_TIME_END];

    // event group
    $SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbEventGroup->getTableName(), dbEventGroup::field_status, dbEventGroup::status_active);
    $grps = array();
    if (!$dbEventGroup->sqlExec($SQL, $grps)) {
      $this->setError($dbEventGroup->getError());
      return false;
    }
    $group = array();
    $group[] = array(
      'selected' => ($event[dbEvent::field_event_group] == -1) ? 1 : 0,
      'value' => -1,
      'text' => $this->lang->translate('- no group -')
    );
    foreach ($grps as $grp) {
      $group[] = array(
        'selected' => ($grp[dbEventGroup::field_id] == $event[dbEvent::field_event_group]) ? 1 : 0,
        'value' => $grp[dbEventGroup::field_id],
        'text' => $grp[dbEventGroup::field_name]
      );
    }

    // status
    $status = array();
    foreach ($dbEvent->status_array as $value => $text) {
      $status[] = array(
        'selected' => ($event[dbEvent::field_status] == $value) ? 1 : 0,
        'value' => $value,
        'text' => $text
      );
    }

    $fields = array(
      'date_from' => array(
        'name' => dbEvent::field_event_date_from,
        'id' => 'datepicker_1',
        'value' => (false !== ($x = strtotime($event[dbEvent::field_event_date_from]))) ? date(CFG_DATE_STR, $x) : ''
      ),
      'date_to' => array(
        'name' => dbEvent::field_event_date_to,
        'id' => 'datepicker_2',
        'value' => (false !== ($x = strtotime($event[dbEvent::field_event_date_to]))) ? date(CFG_DATE_STR, $x) : ''
      ),
      'time_start' => array(
        'name' => self::REQUEST_TIME_START,
        'value' => $time_start
      ),
      'time_end' => array(
        'name' => self::REQUEST_TIME_END,
        'value' => $time_end
      ),
      'publish_date_from' => array(
        'name' => dbEvent::field_publish_date_from,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_publish_date_from]))) ? date(CFG_DATE_STR, $x) : '',
        'id' => 'datepicker_3'
      ),
      'publish_date_to' => array(
        'name' => dbEvent::field_publish_date_to,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_publish_date_to]))) ? date(CFG_DATE_STR, $x) : '',
        'id' => 'datepicker_4'
      ),
      'participants_max' => array(
        'name' => dbEvent::field_participants_max,
        'value' => $event[dbEvent::field_participants_max]
      ),
      'participants_total' => array(
        'name' => dbEvent::field_participants_total,
        'value' => $event[dbEvent::field_participants_total]
      ),
      'deadline' => array(
        'name' => dbEvent::field_deadline,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_deadline]))) ? date(CFG_DATE_STR, $x) : '',
        'id' => 'datepicker_5'
      ),
      'costs' => array(
        'name' => dbEventItem::field_costs,
        'value' => sprintf(CFG_CURRENCY, number_format((float) $event[dbEventItem::field_costs], 2, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR))
      ),
      'group' => array(
        'name' => dbEvent::field_event_group,
        'value' => $group
      ),
      'status' => array(
        'name' => dbEvent::field_status,
        'value' => $status
      ),
      'title' => array(
        'name' => dbEventItem::field_title,
        'value' => $event[dbEventItem::field_title]
      ),
      'short_description' => array(
        'name' => dbEventItem::field_desc_short,
        'value' => self::unsanitizeText($event[dbEventItem::field_desc_short])
      ),
      'long_description' => array(
        'name' => dbEventItem::field_desc_long,
        'value' => self::unsanitizeText($event[dbEventItem::field_desc_long])
      ),
      'location' => array(
        'name' => dbEventItem::field_location,
        'value' => $event[dbEventItem::field_location]
      ),
      'link' => array(
        'name' => dbEventItem::field_desc_link,
        'value' => $event[dbEventItem::field_desc_link]
      ),
      'perma_link' => array(
        'name' => dbEvent::field_perma_link,
        'value' => $event[dbEvent::field_perma_link],
      )
    );

    // check if libraryAdmin exists
    if (file_exists(WB_PATH.'/modules/libraryadmin/inc/class.LABackend.php')) {
      require_once WB_PATH.'/modules/libraryadmin/inc/class.LABackend.php';
      // create instance; if you're not using OOP, use a simple var, like $la
      $libraryAdmin = new LABackend();
      // load the preset
      $libraryAdmin->loadPreset(array(
          'module' => 'kit_event',
          'lib'    => 'lib_jquery',
          'preset' => 'datepicker'
      ));
      // print the preset
      $libraryAdmin->printPreset();
    }

    $data = array(
      'form_name' => 'event_edit',
      'form_action' => self::$page_link,
      'action_name' => self::REQUEST_ACTION,
      'action_value' => self::ACTION_EDIT_CHECK,
      'language' => (LANGUAGE == 'EN') ? '' : strtolower(LANGUAGE),
      'event_name' => dbEvent::field_id,
      'event_value' => $event_id,
      'item_name' => dbEventItem::field_id,
      'item_value' => $item_id,
      'suggestion_name' => self::REQUEST_SUGGESTION,
      'suggestion_value' => -1,
      'message' => array(
          'active' => (int) $this->isMessage(),
          'content' => $this->getMessage()
          ),
      'abort_location' => self::$page_link,
      'event' => $fields
    );
    return $this->getTemplate('event.edit.dwoo', $data);
  } // dlgEditEvent()

  public function checkEditEvent() {
    global $dbEvent;
    global $dbEventItem;

    $event_id = (isset($_REQUEST[dbEvent::field_id]) && ($_REQUEST[dbEvent::field_id] > 0)) ? $_REQUEST[dbEvent::field_id] : -1;
    $item_id = (isset($_REQUEST[dbEvent::field_event_item])) && ($_REQUEST[dbEvent::field_event_item] > 0) ? $_REQUEST[dbEvent::field_event_item] : -1;

    $check_array = array(
      dbEvent::field_event_date_from,
      dbEvent::field_event_date_to,
      dbEvent::field_publish_date_from,
      dbEvent::field_publish_date_to,
      dbEvent::field_participants_max,
      dbEvent::field_deadline,
      dbEventItem::field_costs,
      dbEventItem::field_title,
      dbEventItem::field_desc_short
    );
    // check request
    $checked = true;
    $message = '';
    $start_date_ok = false;
    $end_date_ok = false;
    foreach ($check_array as $request) {
      if (isset($_REQUEST[$request])) {
        switch ($request) :
          case dbEvent::field_event_date_from :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $_REQUEST[$request] = date('Y-m-d H:i:s', $x);
              if (isset($_REQUEST[self::REQUEST_TIME_START]) && !empty($_REQUEST[self::REQUEST_TIME_START])) {
                $time = $_REQUEST[self::REQUEST_TIME_START];
                if (strpos($time, ':') !== false) {
                  list($H, $i) = explode(':', $time);
                }
                else {
                  $H = (int) $time;
                  $i = 0;
                }
                if (($H > 23) || ($H < 0) || ($i > 59) || ($i < 0)) {
                  // invalid time
                  $checked = false;
                  $message .= $this->lang->translate('<p>The time {{ time }} for the field {{ field }} is invalid! Please type in the time in the form <i>HH:mm</i>!</p>',
                       array('time' => $time, 'field' => $this->lang->translate('Time start')));
                }
                else {
                  // time ok
                  $_REQUEST[$request] = date('Y-m-d H:i:s', mktime($H, $i, 0, date('m', $x), date('d', $x), date('Y', $x)));
                  unset($_REQUEST[self::REQUEST_TIME_START]);
                  $start_date_ok = true;
                }
              }
              else {
                unset($_REQUEST[self::REQUEST_TIME_START]);
                $start_date_ok = true;
              }
            }
            else {
              $checked = false;
              $message .= $this->lang->translate('<p>The date {{ date }} for the field {{ field }} is invalid! Please type in the date in the format <i>mm-dd-YYYY</i>.</p>',
                  array('field' => $this->lang->translate('Date from'), 'date' => $_REQUEST[$request]));
            }
            break;
          case dbEvent::field_event_date_to :
            // check event date TO
            $x = strtotime($_REQUEST[$request]);
            if (!$x && $start_date_ok) {
              $x = strtotime($_REQUEST[dbEvent::field_event_date_from]);
            }
            elseif (!$x) {
              $checked = false;
              $message .= $this->lang->translate('<p>The date {{ date }} for the field {{ field }} is invalid! Please type in the date in the format <i>mm-dd-YYYY</i>.</p>',
                  array('field' => $this->lang->translate('Date to'), 'date' => $_REQUEST[$request]));
              break;
            }
            // check time
            $_REQUEST[$request] = date('Y-m-d H:i:s', $x);
            $y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
            if (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) < mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y))) {
              $checked = false;
              $message .= $this->lang->translate('<p>Please check the both dates from and to!</p>');
              break;
            }
            if (isset($_REQUEST[self::REQUEST_TIME_END]) && !empty($_REQUEST[self::REQUEST_TIME_END])) {
              $time = $_REQUEST[self::REQUEST_TIME_END];
              if (strpos($time, ':') !== false) {
                list($H, $i) = explode(':', $time);
              }
              else {
                $H = (int) $time;
                $i = 0;
              }
              if (($H > 23) || ($H < 0) || ($i > 59) || ($i < 0)) {
                // invalid time
                $checked = false;
                $message .= $this->lang->translate('<p>The time {{ time }} for the field {{ field }} is invalid! Please type in the time in the form <i>HH:mm</i>!</p>',
                       array('time' => $time, 'field' => $this->lang->translate('Time end')));
              }
              else {
                // time ok
                $_REQUEST[$request] = date('Y-m-d H:i:s', mktime($H, $i, 0, date('m', $x), date('d', $x), date('Y', $x)));
                unset($_REQUEST[self::REQUEST_TIME_END]);
                $end_date_ok = true;
              }
            }
            else {
              // set time to the end of the day
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23, 59, 0, date('m', $x), date('d', $x), date('Y', $x)));
              unset($_REQUEST[self::REQUEST_TIME_END]);
              $end_date_ok = true;
            }
            break;
          case dbEvent::field_publish_date_from :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
              if ($start_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) > mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
                $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
                $checked = false;
                break;
              }
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $x), date('d', $x) - 14, date('Y', $x)));
            }
            elseif ($start_date_ok) {
              $y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $y), date('d', $y) - 14, date('Y', $y)));
            }
            else {
              $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
              $checked = false;
            }
            break;
          case dbEvent::field_publish_date_to :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST[dbEvent::field_event_date_to]);
              if ($end_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) < mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
                $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
                $checked = false;
                break;
              }
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23, 59, 59, date('n', $x), date('j', $x), date('Y', $x)));
            }
            elseif ($end_date_ok) {
              $y = strtotime($_REQUEST[dbEvent::field_event_date_to]);
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m', $y), date('d', $y), date('Y', $y)));
            }
            else {
              $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
              $checked = false;
            }
            break;
          case dbEvent::field_participants_max :
            $x = (int) $_REQUEST[$request];
            if ($x < 1) $x = -1;
            $_REQUEST[$request] = $x;
            break;
          case dbEvent::field_participants_total :
            $x = (int) $_REQUEST[$request];
            if ($x < 1) $x = 0;
            $_REQUEST[$request] = $x;
            break;
          case dbEventItem::field_costs :
            $x = (float) $_REQUEST[$request];
            if ($x < 1) $x = -1;
            $_REQUEST[$request] = $x;
            break;
          case dbEvent::field_deadline :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
              if ($start_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) > mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
                // Deadline liegt nach dem Veranstaltungstermin
                $message .= $this->lang->translate('<p>The deadline is invalid, please check the date!</p>');
                $checked = false;
              }
              else {
                $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m', $x), date('d', $x), date('Y', $x)));
              }
            }
            elseif ($start_date_ok) {
              $_REQUEST[$request] = $_REQUEST[dbEvent::field_event_date_from];
            }
            else {
              $_REQUEST[$request] = '';
            }
            break;
          case dbEventItem::field_title :
            if (empty($_REQUEST[$request])) {
              $message .= $this->lang->translate('<p>Please insert a event title!</p>');
              $checked = false;
            }
            break;
          case dbEventItem::field_desc_short :
            if (empty($_REQUEST[$request])) {
              $message .= $this->lang->translate('<p>Please type in the short description!</p>');
              $checked = false;
            }
            break;
        endswitch
        ;
      }
    }

    if ($checked) {
      // Datensatz übernehmen
      $event = array(
        dbEvent::field_deadline => $_REQUEST[dbEvent::field_deadline],
        dbEvent::field_event_date_from => $_REQUEST[dbEvent::field_event_date_from],
        dbEvent::field_event_date_to => $_REQUEST[dbEvent::field_event_date_to],
        dbEvent::field_event_group => $_REQUEST[dbEvent::field_event_group],
        dbEvent::field_participants_max => $_REQUEST[dbEvent::field_participants_max],
        dbEvent::field_participants_total => $_REQUEST[dbEvent::field_participants_total],
        dbEvent::field_publish_date_from => $_REQUEST[dbEvent::field_publish_date_from],
        dbEvent::field_publish_date_to => $_REQUEST[dbEvent::field_publish_date_to],
        dbEvent::field_status => $_REQUEST[dbEvent::field_status]
      );
      $item = array(
        dbEventItem::field_costs => $_REQUEST[dbEventItem::field_costs],
        dbEventItem::field_desc_link => $_REQUEST[dbEventItem::field_desc_link],
        dbEventItem::field_desc_long => $_REQUEST[dbEventItem::field_desc_long],
        dbEventItem::field_desc_short => $_REQUEST[dbEventItem::field_desc_short],
        dbEventItem::field_location => $_REQUEST[dbEventItem::field_location],
        dbEventItem::field_title => $_REQUEST[dbEventItem::field_title]
      );

      if ($event_id == -1) {
        // neuer Datensatz
        $new_event = true;
        $item_id = -1;
        if (!$dbEventItem->sqlInsertRecord($item, $item_id)) {
          $this->setError($dbEventItem->getError());
          return false;
        }
        $event[dbEvent::field_event_item] = $item_id;
        if (!$dbEvent->sqlInsertRecord($event, $event_id)) {
          $this->setError($dbEvent->getError());
          return false;
        }
        $message .= $this->lang->translate('<p>The event with the {{ id }} was successfull created.</p>', array('id' => $event_id));
      }
      else {
        // Datensatz aktualisieren
        $new_event = false;
        $where = array(
          dbEventItem::field_id => $item_id
        );
        if (!$dbEventItem->sqlUpdateRecord($item, $where)) {
          $this->setError($dbEventItem->getError());
          return false;
        }
        $where = array(
          dbEvent::field_id => $event_id
        );
        if (!$dbEvent->sqlUpdateRecord($event, $where)) {
          $this->setError($dbEvent->getError());
          return false;
        }
        $message .= $this->lang->translate('<p>The event with the ID {{ id }} was successfull updated.</p>',
            array('id' => $event_id));
      }
      // permaLink pruefen
      $this->checkPermaLink($event_id, $_REQUEST[dbEvent::field_perma_link], $new_event);
      if ($this->isError()) return false;
      if ($this->isMessage()) $message .= $this->getMessage();
      $this->clearMessage();
      unset($_REQUEST[dbEvent::field_perma_link]);

      foreach ($event as $key => $value) {
        unset($_REQUEST[$key]);
      }
      foreach ($item as $key => $value) {
        unset($_REQUEST[$key]);
      }
      $_REQUEST[dbEvent::field_id] = $event_id;

      // iCal Datei schreiben
      if (!$this->createICalFile($event_id)) return false;
      if ($this->isMessage()) $message .= $this->getMessage();
      $this->clearMessage();

      // QR Code Datei schreiben
      if (!$this->createQRCodeFile($event_id)) return false;
      if ($this->isMessage()) $message .= $this->getMessage();
      $this->clearMessage();
    }

    $this->setMessage($message);
    return $this->dlgEditEvent();
  } // checkEditEvent()

  public function createQRCodeFile($event_id) {
    global $dbEvent;
    global $kitLibrary;

    if (!self::$cfgQRCodeCreate) return true;

    $SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbEvent->getTableName(), dbEvent::field_id, $event_id);
    $event = array();
    if (!$dbEvent->sqlExec($SQL, $event)) {
      $this->setError($dbEvent->getError());
      return false;
    }
    if (count($event) < 1) {
      $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id)));
      return false;
    }
    $event = $event[0];

    $c_type = self::$cfgQRCodeContent;

    if ($c_type == 2) {
      // iCal einlesen
      $dir = $kitLibrary->removeLeadingSlash(self::$cfgICalDir);
      $dir = $kitLibrary->addSlash($dir);
      $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
      $filename = $event[dbEvent::field_ical_file];
      if (empty($filename)) {
        // es existiert keine iCal Datei
        $this->setMessage($this->lang->translate('<p>The iCal file does not exists!</p>'));
        return true;
      }
      if (!file_exists($dir_path . $filename)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(tool_error_missing_file, $dir_path . $filename)));
        return false;
      }
      $text = file_get_contents($dir_path . $filename);
    }
    else {
      if (empty($event[dbEvent::field_perma_link])) {
        $this->setMessage($this->lang->translate('<p>There is no permaLink defined!</p>'));
        return true;
      }
      $text = WB_URL . PAGES_DIRECTORY . $event[dbEvent::field_perma_link];
    }

    $level = self::$cfgQRCodeECLevel;
    $size = self::$cfgQRCodeSize;
    $margin = self::$cfgQRCodeMargin;

    $filename = sprintf('%s-%05d.png', date('Ymd-Hi', strtotime($event[dbEvent::field_event_date_from])), $event[dbEvent::field_id]);
    $dir = $kitLibrary->removeLeadingSlash(self::$cfgQRCodeDir);
    $dir = $kitLibrary->addSlash($dir);
    $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
    if (!file_exists($dir_path)) {
      if (!mkdir($dir_path, 0755)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(tool_error_mkdir, $dir_path)));
        return false;
      }
    }

    $QRCode = new QRcode();
    $QRCode->png($text, $dir_path . $filename, $level, $size, $margin);

    $where = array(
      dbEvent::field_id => $event[dbEvent::field_id]
    );
    $data = array(
      dbEvent::field_qrcode_image => $filename
    );
    if (!$dbEvent->sqlUpdateRecord($data, $where)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
      return false;
    }

    return true;
  } // createQRCodeFile()

  public function createICalFile($event_id) {
    global $dbEvent;
    global $dbEventItem;
    global $kitLibrary;

    if (!self::$cfgICalCreate) {
      // keine iCal Dateien anlegen
      return true;
    }

    $SQL = sprintf("SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s.%s='%s'", $dbEvent->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, $dbEvent->getTableName(), dbEvent::field_id, $event_id);
    $event = array();
    if (!$dbEvent->sqlExec($SQL, $event)) {
      $this->setError($dbEvent->getError());
      return false;
    }
    if (count($event) < 1) {
      $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id)));
      return false;
    }
    $event = $event[0];

    // iCal initialisieren und schreiben
    $desc = utf8_fast_entities_to_umlauts(strip_tags($event[dbEventItem::field_desc_long]));
    // $desc =
    // utf8_encode(html_entity_decode(strip_tags($event[dbEventItem::field_desc_long])));

    $vCal = new vcalendar(array(
      'unique_id' => 'kitEvent',
      'language' => strtolower(LANGUAGE)
    ));
    $evt = &$vCal->newComponent('vevent');
    $evt->setProperty('class', 'PUBLIC'); // PUBLIC = Standard
    $evt->setProperty('priority', 0); // 0 = keine Angabe
    $evt->setProperty('status', 'CONFIRMED'); // TENTATIVE, CONFIRMED, CANCELLED
    $evt->setProperty('summary', $event[dbEventItem::field_title]);
    $evt->setProperty('description', $desc);
    list($year, $month, $day, $hour, $minute, $second) = explode('-', date('Y-m-d-H-i-s', strtotime($event[dbEvent::field_event_date_from])));
    $evt->setProperty('dtstart', $year, $month, $day, $hour, $minute, $second);
    list($year, $month, $day, $hour, $minute, $second) = explode('-', date('Y-m-d-H-i-s', strtotime($event[dbEvent::field_event_date_to])));
    $evt->setProperty('dtend', $year, $month, $day, $hour, $minute, $second);
    $evt->setProperty('location', $event[dbEventItem::field_location]);
    $ical = $vCal->createCalendar();
    $filename = sprintf('%s-%05d.ics', date('Ymd-Hi', strtotime($event[dbEvent::field_event_date_from])), $event[dbEvent::field_id]);
    $dir = $kitLibrary->removeLeadingSlash(self::$cfgICalDir);
    $dir = $kitLibrary->addSlash($dir);
    $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
    if (!file_exists($dir_path)) {
      if (!mkdir($dir_path, 0755)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(tool_error_mkdir, $dir_path)));
        return false;
      }
    }
    if (!file_put_contents($dir_path . $filename, $ical)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
          $this->lang->create('Error: cannot create the file {{ file }}!', array('file' => $dir . $filename))));
      return false;
    }

    $where = array(
      dbEvent::field_id => $event[dbEvent::field_id]
    );
    $data = array(
      dbEvent::field_ical_file => $filename
    );
    if (!$dbEvent->sqlUpdateRecord($data, $where)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
      return false;
    }
    return true;
  } // createICalFile()

  public function checkPermaLink($event_id, $perma_link, $new_event = false) {
    global $dbEventGroup;
    global $dbEvent;
    global $kitLibrary;

    if (!self::$cfgPermaLinkCreate) return true;

    $where = array(
      dbEvent::field_id => $event_id
    );
    $event = array();
    if (!$dbEvent->sqlSelectRecord($where, $event)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
      return false;
    }
    if (count($event) < 1) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
          $this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id))));
      return false;
    }
    $event = $event[0];

    if ($new_event) {
      if (empty($perma_link)) {
        if ($event[dbEvent::field_event_group] == -1) return true;
        // pruefen ob ein Pattern angegeben ist
        $where = array(
          dbEventGroup::field_id => $event[dbEvent::field_event_group]
        );
        $group = array();
        if (!$dbEventGroup->sqlSelectRecord($where, $group)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEventGroup->getError()));
          return false;
        }
        if (count($group) < 1) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
              $this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event[dbEvent::field_event_group]))));
          return false;
        }
        $group = $group[0];
        $pattern = $group[dbEventGroup::field_perma_link_pattern];
        $redirect = $group[dbEventGroup::field_redirect_page];
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect, (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
          'act' => 'evt',
          'evt' => 'id',
          'det' => '1',
          'id' => $event[dbEvent::field_id]
        )));
        // kein Muster und Redirect definiert, kein permaLink gesetzt - nix zu
        // tun
        if (empty($pattern) || empty($redirect)) return true;
        // Pattern aktivieren
        $wb_settings = array();
        $kitLibrary->getWBSettings($wb_settings);
        $date = getdate(strtotime($event[dbEvent::field_event_date_from]));
        $pattern_array = array(
          '{$YEAR}',
          '{$MONTH}',
          '{$DAY}',
          '{$ID}',
          '{$EXT}',
          '{$NAME}'
        );
        $values_array = array(
          $date['year'] - 2000,
          sprintf('%02d', $date['mon']),
          sprintf('%02d', $date['mday']),
          $event[dbEvent::field_id],
          $wb_settings['page_extension'],
          $group[dbEventGroup::field_name]
        );

        $perma_link = str_ireplace($pattern_array, $values_array, $pattern);
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL . PAGES_DIRECTORY . $redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $permaLink->getError()));
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        // dbEvent aktualisieren
        $where = array(
          dbEvent::field_id => $event[dbEvent::field_id]
        );
        $data = array(
          dbEvent::field_perma_link => $perma_link
        );
        if (!$dbEvent->sqlUpdateRecord($data, $where)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
          return false;
        }
        $this->setMessage($this->lang->translate('<p>The permaLink {{ link }} was created!</p>', array('link' => $perma_link)));
        return true;
      }
      else {
        // permaLink ist von Hand gesetzt
        if ($event[dbEvent::field_event_group] == -1) return true;
        $where = array(
          dbEventGroup::field_id => $event[dbEvent::field_event_group]
        );
        $group = array();
        if (!$dbEventGroup->sqlSelectRecord($where, $group)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEventGroup->getError()));
          return false;
        }
        if (count($group) < 1) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
              $this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event[dbEvent::field_event_group]))));
          return false;
        }
        $redirect = $group[0][dbEventGroup::field_redirect_page];
        if (empty($redirect)) {
          $this->setMessage($this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>'));
          return false;
        }
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect, (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
          'act' => 'evt',
          'evt' => 'id',
          'det' => '1',
          'id' => $event[dbEvent::field_id]
        )));
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL . PAGES_DIRECTORY . $redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $permaLink->getError()));
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        // dbEvent aktualisieren
        $where = array(
          dbEvent::field_id => $event[dbEvent::field_id]
        );
        $data = array(
          dbEvent::field_perma_link => $perma_link
        );
        if (!$dbEvent->sqlUpdateRecord($data, $where)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
          return false;
        }
        $this->setMessage($this->lang->translate('<p>The permaLink {{ link }} was created!</p>', array('link' => $perma_link)));
        return true;
      }
    }
    elseif ($event[dbEvent::field_perma_link] != $perma_link) {
      // der permaLink wurde geaendert...
      if (empty($event[dbEvent::field_perma_link])) {
        // der permaLink ist neu
        if ($event[dbEvent::field_event_group] == -1) {
          $this->setMessage($this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>'));
          return false;
        }
        $where = array(
          dbEventGroup::field_id => $event[dbEvent::field_event_group]
        );
        $group = array();
        if (!$dbEventGroup->sqlSelectRecord($where, $group)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEventGroup->getError()));
          return false;
        }
        if (count($group) < 1) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
              $this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event[dbEvent::field_event_group]))));
          return false;
        }
        $redirect = $group[0][dbEventGroup::field_redirect_page];
        if (empty($redirect)) {
          $this->setMessage($this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>'));
          return false;
        }
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect, (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
          'act' => 'evt',
          'evt' => 'id',
          'det' => '1',
          'id' => $event[dbEvent::field_id]
        )));
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL . PAGES_DIRECTORY . $redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $permaLink->getError()));
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        // dbEvent aktualisieren
        $where = array(
          dbEvent::field_id => $event[dbEvent::field_id]
        );
        $data = array(
          dbEvent::field_perma_link => $perma_link
        );
        if (!$dbEvent->sqlUpdateRecord($data, $where)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
          return false;
        }
        $this->setMessage($this->lang->translate('<p>The permaLink {{ link }} was created!</p>', array('link' => $perma_link)));
        return true;
      }
      else {
        // permaLink aendern
        $message = '';
        $permaLink = new permaLink();
        // alten permaLink loeschen
        if (!$permaLink->deletePermaLink($event[dbEvent::field_perma_link])) {
          if ($permaLink->isError()) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $permaLink->getError()));
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        $where = array(
          dbEvent::field_id => $event[dbEvent::field_id]
        );
        $data = array(
          dbEvent::field_perma_link => ''
        );
        if (!$dbEvent->sqlUpdateRecord($data, $where)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
          return false;
        }
        $message = $this->lang->translate('<p>The permaLink {{ link }} was deleted!</p>', array('link' => $event[dbEvent::field_perma_link]));
        if (empty($perma_link)) {
          // permaLink wird nur geloescht, kein neuer angelegt...
          $this->setMessage($message);
          return true;
        }
        // neuen permaLink anlegen
        if ($event[dbEvent::field_event_group] == -1) {
          $message .= $this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>');
          $this->setMessage($message);
          return false;
        }
        $where = array(
          dbEventGroup::field_id => $event[dbEvent::field_event_group]
        );
        $group = array();
        if (!$dbEventGroup->sqlSelectRecord($where, $group)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEventGroup->getError()));
          return false;
        }
        if (count($group) < 1) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
              $this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event[dbEvent::field_event_group]))));
          return false;
        }
        $redirect = $group[0][dbEventGroup::field_redirect_page];
        if (empty($redirect)) {
          $message .= $this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>');
          $this->setMessage($message);
          return false;
        }
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect, (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
          'act' => 'evt',
          'evt' => 'id',
          'det' => '1',
          'id' => $event[dbEvent::field_id]
        )));
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL . PAGES_DIRECTORY . $redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $permaLink->getError()));
            return false;
          }
          $message .= $permaLink->getMessage();
          $this->setMessage($message);
          return false;
        }
        // dbEvent aktualisieren
        $where = array(
          dbEvent::field_id => $event[dbEvent::field_id]
        );
        $data = array(
          dbEvent::field_perma_link => $perma_link
        );
        if (!$dbEvent->sqlUpdateRecord($data, $where)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
          return false;
        }
        $message .= $this->lang->translate('<p>The permaLink {{ link }} was created!</p>', array('link' => $perma_link));
        $this->setMessage($message);
        return true;
      }
    }
    // nothing to do...
    return true;
  } // checkPermaLink

  /**
   * Create or edit event groups
   *
   * @return STR dialog
   */
  public function dlgEditGroup() {
    global $dbEventGroup;
    global $parser;
    global $kitLibrary;
    global $database;

    $group_id = (isset($_REQUEST[dbEventGroup::field_id]) && ($_REQUEST[dbEventGroup::field_id] > 0)) ? $_REQUEST[dbEventGroup::field_id] : -1;

    // get active event group
    if ($group_id > 0) {
      $SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbEventGroup->getTableName(), dbEventGroup::field_id, $group_id);
      $active_group = array();
      if (!$dbEventGroup->sqlExec($SQL, $active_group)) {
        $this->setError($dbEventGroup->getError());
        return false;
      }
      if (count($active_group) < 1) {
        $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $group_id)));
        return false;
      }
      $active_group = $active_group[0];
    }
    else {
      // new group
      $active_group = $dbEventGroup->getFields();
      $active_group[dbEventGroup::field_status] = dbEventGroup::status_active;
    }

    // get all groups
    $SQL = sprintf("SELECT %s, %s FROM %s WHERE %s!='%s' ORDER BY %s", dbEventGroup::field_id, dbEventGroup::field_name, $dbEventGroup->getTableName(), dbEventGroup::field_status, dbEventGroup::status_deleted, dbEventGroup::field_name);
    $all_groups = array();
    if (!$dbEventGroup->sqlExec($SQL, $all_groups)) {
      $this->setError($dbEventGroup->getError());
      return false;
    }

    // event groups
    $grps = array();
    $grps[] = array(
      'selected' => ($group_id == -1) ? 1 : 0,
      'value' => -1,
      'text' => $this->lang->translate('- create a new group -')
    );
    foreach ($all_groups as $grp) {
      $grps[] = array(
        'selected' => ($grp[dbEventGroup::field_id] == $group_id) ? 1 : 0,
        'value' => $grp[dbEventGroup::field_id],
        'text' => $grp[dbEventGroup::field_name]
      );
    }

    // group status
    $status = array();
    $status_array = $dbEventGroup->status_array;
    if ($group_id == -1) unset($status_array[dbEventGroup::status_deleted]);
    foreach ($status_array as $value => $name) {
      $status[] = array(
        'selected' => ($value == $active_group[dbEventGroup::field_status]) ? 1 : 0,
        'value' => $value,
        'text' => $name
      );
    }

    // REDIRECT URLs Array erstellen
    $wb_settings = array();
    $kitLibrary->getWBSettings($wb_settings);
    $ext = $wb_settings['page_extension'];
    $SQL = sprintf("SELECT link FROM %spages ORDER BY link ASC", TABLE_PREFIX);
    if (false == ($pages = $database->query($SQL))) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    $page_array = array();
    $page_array[] = array(
      'value' => -1,
      'text' => $this->lang->translate('- select the redirect page -')
    );
    while (false !== ($page = $pages->fetchRow(MYSQL_ASSOC))) {
      $page_array[] = array(
        'value' => $page['link'] . $ext,
        'text' => $page['link'] . $ext
      );
    }

    $group = array(
      'group' => array(
        'name' => dbEventGroup::field_id,
        'value' => $grps,
        'location' => sprintf('javascript:execOnChange(\'%s\', \'%s\');', sprintf('%s&amp;%s=%s%s&amp;%s=', self::$page_link, self::REQUEST_ACTION, self::ACTION_GROUP, (defined('LEPTON_VERSION') && isset($_GET['leptoken'])) ? sprintf('&amp;leptoken=%s', $_GET['leptoken']) : '', dbEventGroup::field_id), dbEventGroup::field_id),
      ),
      'name' => array(
        'name' => dbEventGroup::field_name,
        'value' => $active_group[dbEventGroup::field_name],
      ),
      'desc' => array(
        'name' => dbEventGroup::field_desc,
        'value' => $active_group[dbEventGroup::field_desc],
      ),
      'status' => array(
        'name' => dbEventGroup::field_status,
        'label' => event_label_status,
        'value' => $status,
      ),
      'perma_pattern' => array(
        'name' => dbEventGroup::field_perma_link_pattern,
        'value' => $active_group[dbEventGroup::field_perma_link_pattern],
      ),
      'redirect_page' => array(
        'name' => dbEventGroup::field_redirect_page,
        'value' => $active_group[dbEventGroup::field_redirect_page],
        'options' => $page_array,
      )
    );

    $data = array(
      'form_name' => 'event_group',
      'form_action' => self::$page_link,
      'action_name' => self::REQUEST_ACTION,
      'action_value' => self::ACTION_GROUP_CHECK,
      'message' => array(
          'active' => (int) $this->isMessage(),
          'content' => $this->getMessage()
          ),
      'group' => $group,
      'abort_location' => self::$page_link
    );
    return $this->getTemplate('event.group.dwoo', $data);
  } // dlgEditGroup()

  /**
   * check the edited event group and create a new group
   * or update the desired group
   *
   * @return STR dlgEditGroup()
   */
  public function checkEditGroup() {
    global $dbEventGroup;
    $group_id = $_REQUEST[dbEventGroup::field_id];
    if (empty($_REQUEST[dbEventGroup::field_name])) {
      $this->setMessage($this->lang->translate('<p>The event group must be named!</p>'));
      return $this->dlgEditGroup();
    }
    $data = array(
      dbEventGroup::field_name => $_REQUEST[dbEventGroup::field_name],
      dbEventGroup::field_desc => $_REQUEST[dbEventGroup::field_desc],
      dbEventGroup::field_status => $_REQUEST[dbEventGroup::field_status],
      dbEventGroup::field_perma_link_pattern => $_REQUEST[dbEventGroup::field_perma_link_pattern],
      dbEventGroup::field_redirect_page => ($_REQUEST[dbEventGroup::field_redirect_page] == -1) ? '' : $_REQUEST[dbEventGroup::field_redirect_page]
    );

    if ($group_id > 0) {
      // existing group
      $where = array(
        dbEventGroup::field_id => $group_id
      );
      if (!$dbEventGroup->sqlUpdateRecord($data, $where)) {
        $this->setError($dbEventGroup->getError());
        return false;
      }
      $this->setMessage($this->lang->translate('<p>The event group with the ID {{ id }} was successfull updated</p>', array('id' => $group_id)));
    }
    else {
      // new group - check if name is already in use
      $SQL = sprintf("SELECT * FROM %s WHERE %s='%s' AND %s!='%s'", $dbEventGroup->getTableName(), dbEventGroup::field_name, $data[dbEventGroup::field_name], dbEventGroup::field_status, dbEventGroup::status_deleted);
      $check = array();
      if (!$dbEventGroup->sqlExec($SQL, $check)) {
        $this->setError($dbEventGroup->getError());
        return false;
      }
      if (count($check) > 0) {
        $this->setMessage($this->lang->translate('<p>The event group with the name {{ name }} already exists!</p>',
            array('name' => $data[dbEventGroup::field_name])));
      }
      elseif (!$dbEventGroup->sqlInsertRecord($data, $group_id)) {
        $this->setError($dbEventGroup->getError());
        return false;
      }
      else {
        $this->setMessage($this->lang->translate('<p>The event group with the ID {{ id }} was successfull created.</p>',
            array('id' => $group_id)));
      }
    }
    return $this->dlgEditGroup();
  } // checkEditGroup()

  public function dlgAbout() {
    $data = array(
      'version' => sprintf('%01.2f', $this->getVersion()),
      'img_url' => self::$img_url . '/kit_event_logo_424x283.jpg',
      'release_notes' => file_get_contents(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/CHANGELOG')
    );
    return $this->getTemplate('about.dwoo', $data);
  } // dlgAbout()

  public function dlgMessages() {
    global $dbEventOrder;
    global $dbEvent;
    global $dbEventItem;

    $SQL = sprintf("SELECT * FROM %s,%s,%s WHERE %s.%s=%s.%s AND %s.%s=%s.%s ORDER BY %s DESC LIMIT 100", $dbEvent->getTableName(), $dbEventOrder->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_id, $dbEventOrder->getTableName(), dbEventOrder::field_event_id, $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, dbEventOrder::field_order_date);
    $messages = array();
    if (!$dbEventOrder->sqlExec($SQL, $messages)) {
      $this->setError($dbEventOrder->getError());
      return false;
    }

    $items = '';
    $rows = array();
    foreach ($messages as $message) {
      $dt = strtotime($message[dbEventOrder::field_confirm_order]);
      $declared = (checkdate(date('n', $dt), date('j', $dt), date('Y', $dt))) ? $this->lang->translate('Yes') : '';
      $name = sprintf('%s, %s', $message[dbEventOrder::field_last_name], $message[dbEventOrder::field_first_name]);
      if (strlen($name) < 3) $name = '';
      $rows[] = array(
        'order_date_link' => sprintf('%s&%s=%s&%s=%s', self::$page_link, self::REQUEST_ACTION, self::ACTION_MESSAGES_DETAIL, dbEventOrder::field_id, $message[dbEventOrder::field_id]),
        'order_date' => date(CFG_DATETIME_STR, strtotime($message[dbEventOrder::field_order_date])),
        'email' => $message[dbEventOrder::field_email],
        'name' => $name,
        'event' => $message[dbEventItem::field_title],
        'event_date' => date(CFG_DATE_STR, strtotime($message[dbEvent::field_event_date_from])),
        'declared' => $declared,
        'message' => $message[dbEventOrder::field_message]
      );
    }

    $data = array(
      'intro' => ($this->isMessage()) ? $this->getMessage() : '',
      'is_intro' => ($this->isMessage()) ? 0 : 1,
      'rows' => $rows,
      'order_date_name' => dbEventOrder::field_order_date,
      'email_name' => dbEventOrder::field_email,
      'name_name' => dbEventOrder::field_last_name,
      'event_name' => dbEventOrder::field_event_id,
      'event_date_name' => dbEvent::field_event_date_from,
      'declared_name' => dbEventOrder::field_confirm_order,
      'message_name' => dbEventOrder::field_message,
    );
    return $this->getTemplate('order.list.dwoo', $data);
  } // dlgMessages()

  public function dlgMessageDetail() {
    global $dbEventOrder;
    global $dbEvent;
    global $dbEventItem;
    global $parser;

    $order_id = (isset($_REQUEST[dbEventOrder::field_id])) ? (int) $_REQUEST[dbEventOrder::field_id] : -1;

    $SQL = sprintf("SELECT * FROM %s,%s,%s WHERE %s.%s=%s.%s AND %s.%s=%s.%s AND %s=%s", $dbEvent->getTableName(), $dbEventOrder->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_id, $dbEventOrder->getTableName(), dbEventOrder::field_event_id, $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, dbEventOrder::field_id, $order_id);
    $detail = array();
    if (!$dbEventOrder->sqlExec($SQL, $detail)) {
      $this->setError($dbEventOrder->getError());
      return false;
    }
    if (count($detail) < 1) {
      $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $order_id)));
      return false;
    }
    $detail = $detail[0];

    $dt = strtotime($detail[dbEventOrder::field_confirm_order]);
    $declared = (checkdate(date('n', $dt), date('j', $dt), date('Y', $dt))) ? date(CFG_DATETIME_STR, $dt) : $this->lang->translate('No');

    $label_free_1 = substr($detail[dbEventOrder::field_free_1], 0, strpos($detail[dbEventOrder::field_free_1], '|'));
    $label_free_2 = substr($detail[dbEventOrder::field_free_2], 0, strpos($detail[dbEventOrder::field_free_2], '|'));
    $label_free_3 = substr($detail[dbEventOrder::field_free_3], 0, strpos($detail[dbEventOrder::field_free_3], '|'));
    $label_free_4 = substr($detail[dbEventOrder::field_free_4], 0, strpos($detail[dbEventOrder::field_free_4], '|'));
    $label_free_5 = substr($detail[dbEventOrder::field_free_5], 0, strpos($detail[dbEventOrder::field_free_5], '|'));

    $data = array(
      'title' => $detail[dbEventOrder::field_title],
      'first_name' => $detail[dbEventOrder::field_first_name],
      'last_name' => $detail[dbEventOrder::field_last_name],
      'company' => $detail[dbEventOrder::field_company],
      'street' => $detail[dbEventOrder::field_street],
      'zip' => $detail[dbEventOrder::field_zip],
      'city' => $detail[dbEventOrder::field_city],
      'email' => $detail[dbEventOrder::field_email],
      'phone' => $detail[dbEventOrder::field_phone],
      'best_time' => $detail[dbEventOrder::field_best_time],
      'event' => $detail[dbEventItem::field_title],
      'event_date' => date(CFG_DATE_STR, strtotime($detail[dbEvent::field_event_date_from])),
      'declared' => $declared,
      'message' => $detail[dbEventOrder::field_message],
      'free_1' => substr($detail[dbEventOrder::field_free_1], strpos($detail[dbEventOrder::field_free_1], '|') + 1),
      'free_2' => substr($detail[dbEventOrder::field_free_2], strpos($detail[dbEventOrder::field_free_2], '|') + 1),
      'free_3' => substr($detail[dbEventOrder::field_free_3], strpos($detail[dbEventOrder::field_free_3], '|') + 1),
      'free_4' => substr($detail[dbEventOrder::field_free_4], strpos($detail[dbEventOrder::field_free_4], '|') + 1),
      'free_5' => substr($detail[dbEventOrder::field_free_5], strpos($detail[dbEventOrder::field_free_5], '|') + 1),
      'back_link' => sprintf('%s&%s=%s', self::$page_link, self::REQUEST_ACTION, self::ACTION_MESSAGES),
    );
    return $this->getTemplate('order.detail.dwoo', $data);
  } // dlgMessageDetail()


  /**
   * kitEvent settings
   *
   * @return string dialog
   */
  protected function dlgConfig() {
    // set the link to call the dlgConfig()
    $link = sprintf('%s&%s',
        self::$page_link,
        http_build_query(array(
            self::REQUEST_ACTION => self::ACTION_CONFIG
        )));
    // set the abort link
    $abort = sprintf('%s&%s',
        self::$page_link,
        http_build_query(array(
            self::REQUEST_ACTION => self::ACTION_DEFAULT
        )));
    // exec manufakturConfig
    $dialog = new manufakturConfigDialog('kit_event', 'kitEvent', $link, $abort);
    return $dialog->action();
  } // dlgSettings()

} // class eventBackend

?>