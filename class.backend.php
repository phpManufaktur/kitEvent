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

require_once LEPTON_PATH.'/modules/kit/class.interface.php';
global $kitContactInterface;

class eventBackend {

  const REQUEST_ACTION = 'kea';
  const REQUEST_SUB_ACTION = 'sub';
  const REQUEST_ITEMS = 'its';
  const REQUEST_TIME_START = 'ets';
  const REQUEST_TIME_END = 'ete';
  const REQUEST_SUGGESTION = 'sgg';
  const REQUEST_SHOW_ALL = 'sa';
  const REQUEST_COPY_ALL = 'ca';
  const REQUEST_EVENT_GROUP = 'egrp';

  const ACTION_ABOUT = 'abt';
  const ACTION_CONFIG = 'cfg';
  const ACTION_DEFAULT = 'def';
  const ACTION_DELETE = 'del';
  const ACTION_EDIT = 'edt';
  const ACTION_EDIT_CHECK = 'edtc';
  const ACTION_GROUP = 'grp';
  const ACTION_GROUP_CHECK = 'grpc';
  const ACTION_LIST = 'lst';
  const ACTION_MESSAGES = 'msg';
  const ACTION_MESSAGES_DETAIL = 'msgd';
  const ACTION_SETTINGS = 'set';
  const ACTION_KIT = 'kit';
  const ACTION_CHECK_SUGGEST = 'sugc';

  // needed for permaLink - must be similiar to the const in class.frontend.php!
  const REQUEST_EVENT = 'evt';
  const REQUEST_EVENT_DETAIL = 'det';
  const REQUEST_EVENT_ID = 'id';
  const ACTION_EVENT = 'evt';
  const VIEW_ID = 'id';

  private static $tab_navigation_array = array(
    self::ACTION_LIST => 'TAB_LIST',
    self::ACTION_EDIT => 'TAB_EDIT',
    self::ACTION_MESSAGES => 'TAB_MESSAGES',
    self::ACTION_KIT => 'KeepInTouch',
    self::ACTION_SETTINGS => 'Settings',
    self::ACTION_ABOUT => 'TAB_ABOUT',
  );

  private static $page_link = '';
  private static $img_url = '';
  private static $template_path = '';
  private static $template_url = '';
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
  protected static $cfgDescriptionLong = null;
  protected static $cfgDescriptionShort = null;
  protected static $cfgFreeFieldLabel_1 = null;
  protected static $cfgFreeFieldUseHTML_1 = null;
  protected static $cfgFreeFieldLabel_2 = null;
  protected static $cfgFreeFieldUseHTML_2 = null;
  protected static $cfgFreeFieldLabel_3 = null;
  protected static $cfgFreeFieldUseHTML_3 = null;
  protected static $cfgFreeFieldLabel_4 = null;
  protected static $cfgFreeFieldUseHTML_4 = null;
  protected static $cfgFreeFieldLabel_5 = null;
  protected static $cfgFreeFieldUseHTML_5 = null;

  protected $lang = null;

  public function __construct() {
    global $I18n;
    global $manufakturConfig;

    $this->lang = $I18n;
    self::$page_link = ADMIN_URL . '/admintools/tool.php?tool=kit_event';
    self::$template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/templates/backend/';
    self::$template_url = WB_URL . '/modules/' . basename(dirname(__FILE__)) . '/templates/backend/';
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
    self::$cfgDescriptionLong = $manufakturConfig->getValue('cfg_event_use_long_description', 'kit_event');
    self::$cfgDescriptionShort = $manufakturConfig->getValue('cfg_event_use_short_description', 'kit_event');
    self::$cfgFreeFieldLabel_1 = $manufakturConfig->getValue('cfg_event_free_field_1', 'kit_event');
    self::$cfgFreeFieldUseHTML_1 = $manufakturConfig->getValue('cfg_event_free_field_1_use_html', 'kit_event');
    self::$cfgFreeFieldLabel_2 = $manufakturConfig->getValue('cfg_event_free_field_2', 'kit_event');
    self::$cfgFreeFieldUseHTML_2 = $manufakturConfig->getValue('cfg_event_free_field_2_use_html', 'kit_event');
    self::$cfgFreeFieldLabel_3 = $manufakturConfig->getValue('cfg_event_free_field_3', 'kit_event');
    self::$cfgFreeFieldUseHTML_3 = $manufakturConfig->getValue('cfg_event_free_field_3_use_html', 'kit_event');
    self::$cfgFreeFieldLabel_4 = $manufakturConfig->getValue('cfg_event_free_field_4', 'kit_event');
    self::$cfgFreeFieldUseHTML_4 = $manufakturConfig->getValue('cfg_event_free_field_4_use_html', 'kit_event');
    self::$cfgFreeFieldLabel_5 = $manufakturConfig->getValue('cfg_event_free_field_5', 'kit_event');
    self::$cfgFreeFieldUseHTML_5 = $manufakturConfig->getValue('cfg_event_free_field_5_use_html', 'kit_event');

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
    if (file_exists(self::$template_path.LANGUAGE.'/'.$template)) {
      $template_path = self::$template_path.LANGUAGE.'/';
      $template_data['TEMPLATE_URL'] = self::$template_url.LANGUAGE;
    }
    else {
      $template_path = self::$template_path.'DE/';
      $template_data['TEMPLATE_URL'] = self::$template_url.'DE';
    }
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
      'item_desc_long',
      'item_desc_short'
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
      case self::ACTION_DELETE:
        $this->show(self::ACTION_MESSAGES, $this->actionDelete());
        break;
      case self::ACTION_EDIT :
        $this->show(self::ACTION_EDIT, $this->dlgEditEvent());
        break;
      case self::ACTION_CHECK_SUGGEST:
        $this->show(self::ACTION_EDIT, $this->checkSuggestEvent());
        break;
      case self::ACTION_EDIT_CHECK :
        $this->show(self::ACTION_EDIT, $this->checkEditEvent());
        break;
      case self::ACTION_MESSAGES :
        $this->show(self::ACTION_MESSAGES, $this->dlgMessages());
        break;
      case self::ACTION_MESSAGES_DETAIL :
        $this->show(self::ACTION_MESSAGES, $this->dlgMessageDetail());
        break;
      case self::ACTION_SETTINGS:
        $this->show(self::ACTION_SETTINGS, $this->actionConfiguration());
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
      if ($key == self::ACTION_KIT)
        $link = ADMIN_URL.'/admintools/tool.php?tool=kit&act=list';
      else
        $link = sprintf('%s&%s=%s', self::$page_link, self::REQUEST_ACTION, $key);
      $navigation[] = array(
        'active' => ($key == $action) ? 1 : 0,
        'url' => $link,
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
    global $database;

    $tke = TABLE_PREFIX.'mod_kit_event';
    $tkei = TABLE_PREFIX.'mod_kit_event_item';

    if (isset($_REQUEST[self::REQUEST_SHOW_ALL]) && ($_REQUEST[self::REQUEST_SHOW_ALL] == 1)) {
      $SQL = "SELECT * FROM `$tke`, `$tkei` WHERE $tke.item_id=$tkei.item_id AND `evt_status`!='-1' ORDER BY `evt_event_date_from`";
      $this->setMessage($this->lang->translate('<p>All events are shown!</p>'));
    }
    else {
      $start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
      $SQL = "SELECT * FROM `$tke`, `$tkei` WHERE $tke.item_id=$tkei.item_id AND `evt_status`!='-1' AND `evt_event_date_from`>='$start_date' ORDER BY `evt_event_date_from` ASC";
    }
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }

    $items = '';
    $rows = array();
    while (false !== ($event = $query->fetchRow(MYSQL_ASSOC))) {
      // get the group name
      $SQL = "SELECT `group_name` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='{$event['group_id']}'";
      $grp = $database->get_one($SQL);
      if ($database->is_error()) {
        $this->setError($database->get_error());
        return false;
      }

      $group = -1;
      $rows[] = array(
        'id_name' => 'evt_id',
        'id_link' => sprintf('%s&%s', self::$page_link, http_build_query(array(
            self::REQUEST_ACTION => self::ACTION_EDIT,
            'evt_id' => $event['evt_id']
            ))),
        'id' => sprintf('%04d', $event['evt_id']),
        'date_from_name' => 'evt_event_date_from',
        'date_from' => date(CFG_DATETIME_STR, strtotime($event['evt_event_date_from'])),
        'date_to_name' => 'evt_event_date_to',
        'date_to' => date(CFG_DATETIME_STR, strtotime($event['evt_event_date_to'])),
        'group_name' => 'group_id',
        'group' => $grp,
        'part_max_name' => 'evt_participants_max',
        'part_max' => $event['evt_participants_max'],
        'part_total_name' => 'evt_participants_total',
        'part_total' => $event['evt_participants_total'],
        'deadline_name' => 'evt_deadline',
        'deadline' => date(CFG_DATE_STR, strtotime($event['evt_deadline'])),
        'title_name' => 'item_title',
        'title' => $event['item_title']
      );
    }

    // check if libraryAdmin exists
    if (file_exists(WB_PATH.'/modules/libraryadmin/inc/class.LABackend.php')) {
      require_once WB_PATH.'/modules/libraryadmin/inc/class.LABackend.php';
      // create instance; if you're not using OOP, use a simple var, like $la
      $libraryAdmin = new LABackend();
      // load the preset
      $libraryAdmin->loadPreset(array(
          'module' => 'kit_event',
          'lib'    => 'lib_jquery',
          'preset' => 'dataTable'
      ));
      // print the preset
      $libraryAdmin->printPreset();
    }

    $data = array(
      'message' => array(
          'active' => (int) $this->isMessage(),
          'content' => $this->getMessage()
          ),
      'rows' => $rows,
      'show_all_link' => sprintf('%s&%s', self::$page_link, http_build_query(array(
          self::REQUEST_ACTION => self::ACTION_LIST,
          self::REQUEST_SHOW_ALL => 1
          ))),
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
    global $database;

    $tke = TABLE_PREFIX.'mod_kit_event';
    $tkei = TABLE_PREFIX.'mod_kit_event_item';

    $SQL = "SELECT $tkei.item_id, evt_event_date_from, item_title FROM `$tke`, `$tkei` ".
        "WHERE $tke.item_id=$tkei.item_id AND `evt_status`!='-1' ORDER BY `evt_event_date_from` LIMIT 20";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }

    $suggest_options = array();
    while (false !== ($event = $query->fetchRow(MYSQL_ASSOC))) {
      $suggest_options[] = array(
        'value' => $event['item_id'],
        'text' => sprintf('[ %s ] %s', date(CFG_DATE_STR, strtotime($event['evt_event_date_from'])), $event['item_title'])
      );
    }

    // gather the available event groups
    $SQL = "SELECT `group_id`, `group_name` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_status`='1'";

    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    $event_groups = array();
    while (false !== ($group = $query->fetchRow(MYSQL_ASSOC))) {
      $event_groups[] = array(
          'value' => $group['group_id'],
          'text' => $group['group_name']
          );
    }

    $data = array(
        'form' => array(
            'name' => 'event_suggest',
            'action' => self::$page_link
            ),
        'action' => array(
            'name' => self::REQUEST_ACTION,
            'value' => self::ACTION_CHECK_SUGGEST
            ),
        'suggest' => array(
            'name' => self::REQUEST_SUGGESTION,
            'value' => -1,
            'options' => $suggest_options
            ),
        'copy_datetime' => array(
            'name' => self::REQUEST_COPY_ALL,
            'value' => 1
            ),
        'event_group' => array(
            'name' => self::REQUEST_EVENT_GROUP,
            'value' => -1,
            'options' => $event_groups
            )
    );
    return $this->getTemplate('event.suggest.dwoo', $data);
  } // dlgSuggestEvent()

  private function checkSuggestEvent() {
    global $database;

    // get the suggested event
    $suggest_id = (isset($_REQUEST[self::REQUEST_SUGGESTION])) ? (int) $_REQUEST[self::REQUEST_SUGGESTION] : -1;

    if ($suggest_id > 0)
      return $this->dlgEditEvent();

    // check if already isset a event group
    $event_group = (isset($_REQUEST[self::REQUEST_EVENT_GROUP])) ? (int) $_REQUEST[self::REQUEST_EVENT_GROUP] : -1;

    if ($event_group > 0)
      return $this->dlgEditEvent();

    // the user must specify a event group

    $copy_datetime = (int) isset($_REQUEST[self::REQUEST_COPY_ALL]);

    // gather the available event groups
    $SQL = "SELECT `group_id`, `group_name` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_status`='1'";

    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    $event_groups = array();
    while (false !== ($group = $query->fetchRow(MYSQL_ASSOC))) {
      $event_groups[] = array(
          'value' => $group['group_id'],
          'text' => $group['group_name']
      );
    }

    $data = array(
        'form' => array(
            'name' => 'event_suggest',
            'action' => self::$page_link
            ),
        'action' => array(
            'name' => self::REQUEST_ACTION,
            'value' => self::ACTION_CHECK_SUGGEST
            ),
        'suggest' => array(
            'name' => self::REQUEST_SUGGESTION,
            'value' => $suggest_id,
            ),
        'copy_datetime' => array(
            'name' => self::REQUEST_COPY_ALL,
            'value' => $copy_datetime
            ),
        'event_group' => array(
            'name' => self::REQUEST_EVENT_GROUP,
            'value' => -1,
            'options' => $event_groups,
            'count' => count($event_group)
            )
        );
    return $this->getTemplate('event.group.dwoo', $data);
  } // checkSuggestEvent()

  protected function getDistributionValue($identifier) {
    global $database;

    $SQL = "SELECT `array_value` FROM `".TABLE_PREFIX."mod_kit_contact_array_cfg` WHERE `array_identifier`='$identifier'";
    return $database->get_one($SQL, MYSQL_ASSOC);
  } // getDistributionValue()

  public function dlgEditEvent() {
    global $database;
    global $kitContactInterface;

    $event_id = (isset($_REQUEST['evt_id']) && ($_REQUEST['evt_id'] > 0)) ? $_REQUEST['evt_id'] : -1;

    if ($event_id !== -1) {
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
      $event = $query->fetchRow(MYSQL_ASSOC);

      $item_id = $event['item_id'];
      if ((date('H', strtotime($event['evt_event_date_from'])) !== 0) && (date('i', strtotime($event['evt_event_date_from'])) !== 0)) {
        $time_start = date(CFG_TIME_STR, strtotime($event['evt_event_date_from']));
      }
      else {
        $time_start = '';
      }
      if ((date('H', strtotime($event['evt_event_date_to'])) !== 0) && (date('i', strtotime($event['evt_event_date_to'])) !== 0)) {
        $time_end = date(CFG_TIME_STR, strtotime($event['evt_event_date_to']));
      }
      else {
        $time_end = '';
      }
    }
    elseif (!isset($_REQUEST[self::REQUEST_SUGGESTION])) {
      // erster Aufruf - Datenuebernahme von bestehenden Events anbieten
      $SQL = "SELECT `group_id` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_status`='1'";
      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }
      if ($query->numRows() < 1) {
        // there is no event group available, user must first create a event group
        $this->setMessage($this->lang->translate('<p>Before you can create the first event you must define a event group at least.</p>'));
        $_REQUEST[self::REQUEST_ACTION] = self::ACTION_SETTINGS;
        $_REQUEST[self::REQUEST_SUB_ACTION] = self::ACTION_GROUP;
        $this->action();
        return true;
      }
      return $this->dlgSuggestEvent();
    }
    elseif (isset($_REQUEST[self::REQUEST_SUGGESTION]) && ($_REQUEST[self::REQUEST_SUGGESTION] != -1)) {
      $item_id = -1;
      // get the field names from mod_kit_event
      $SQL = "SHOW FIELDS FROM `".TABLE_PREFIX."mod_kit_event`";
      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }
      $event = array();
      while (false !== ($field = $query->fetchRow(MYSQL_ASSOC)))
        $event[$field['Field']] = '';

      $event['evt_status'] = 1;
      $time_start = '';
      $time_end = '';
      $item_id = (int) $_REQUEST[self::REQUEST_SUGGESTION];
      $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event_item` WHERE `item_id`='$item_id'";
      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }
      $item = $query->fetchRow(MYSQL_ASSOC);

      $event = array_merge($event, $item);
      // check if all data should be taken
      if (isset($_REQUEST[self::REQUEST_COPY_ALL])) {
        $suggest_id = (int) $_REQUEST[self::REQUEST_SUGGESTION];
        $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event` WHERE `evt_id`='$suggest_id'";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }
        $evt = $query->fetchRow(MYSQL_ASSOC);

        // we dont need participants total and the permaLink
        unset($evt['evt_id']);
        unset($evt['item_id']);
        unset($evt['evt_participants_total']);
        unset($evt['evt_perma_link']);
        unset($evt['evt_ical_file']);
        unset($evt['evt_qrcode_image']);
        $evt['evt_status'] = 1;
        $event = array_merge($event, $evt);
        if ((date('H', strtotime($event['evt_event_date_from'])) !== 0) && (date('i', strtotime($event['evt_event_date_from'])) !== 0)) {
          $time_start = date(CFG_TIME_STR, strtotime($event['evt_event_date_from']));
        }
        else {
          $time_start = '';
        }
        if ((date('H', strtotime($event['evt_event_date_to'])) !== 0) && (date('i', strtotime($event['evt_event_date_to'])) !== 0)) {
          $time_end = date(CFG_TIME_STR, strtotime($event['evt_event_date_to']));
        }
        else {
          $time_end = '';
        }
      }
      $this->setMessage($this->lang->translate('<p>This event was taken from the previous event with the ID {{ id }}</p>',
          array('id' => $_REQUEST[self::REQUEST_SUGGESTION])));
    }
    else {
      $item_id = -1;
      // get the field names from mod_kit_event
      $SQL = "SHOW FIELDS FROM `".TABLE_PREFIX."mod_kit_event`";
      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }
      $event = array();
      while (false !== ($field = $query->fetchRow(MYSQL_ASSOC)))
        $event[$field['Field']] = '';

      // set status to active
      $event['evt_status'] = 1;

      // set the event group
      $event['group_id'] = (isset($_REQUEST[self::REQUEST_EVENT_GROUP])) ? (int) $_REQUEST[self::REQUEST_EVENT_GROUP] : -1;

      // get the field names from mod_kit_event_item
      $SQL = "SHOW FIELDS FROM `".TABLE_PREFIX."mod_kit_event_item`";
      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }
      $items = array();
      while (false !== ($field = $query->fetchRow(MYSQL_ASSOC)))
        $items[$field['Field']] = '';

      $event = array_merge($event, $items);
      $time_start = '';
      $time_end = '';
    }

    foreach ($event as $key => $value) {
      if (isset($_REQUEST[$key])) {
        switch ($key) :
          case 'evt_event_date_from' :
            if (false !== ($x = strtotime($_REQUEST[$key]))) {
              $event[$key] = date('Y-m-d H:i:s', $x);
              $time_start = ((date('H', $x) !== 0) || (date('i', $x) !== 0)) ? date(CFG_TIME_STR, $x) : '';
            }
            break;
          case 'evt_event_date_to' :
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
    $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_status`='1'";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    $grps = array();
    while (false !== ($grp = $query->fetchRow(MYSQL_ASSOC)))
      $grps[] = $grp;
    $group = array();
    foreach ($grps as $grp) {
      $group[] = array(
        'selected' => ($grp['group_id'] == $event['group_id']) ? 1 : 0,
        'value' => $grp['group_id'],
        'text' => $grp['group_name']
      );
    }

    $status = array(
        'ACTIVE' => array(
            'selected' => ($event['evt_status'] == 1) ? 1 : 0,
            'value' => 1,
            'text' => 'ACTIVE'
            ),
        'LOCKED' => array(
            'selected' => ($event['evt_status'] == 0) ? 1 : 0,
            'value' => 0,
            'text' => 'LOCKED'
            ),
        'DELETED' => array(
            'selected' => ($event['evt_status'] == -1) ? 1 : 0,
            'value' => -1,
            'text' => 'DELETED'
            )
        );

    $organizer_ids = array();
    $organizer_contact = array();
    $organizer_default = -1;

    $location_ids = array();
    $location_contact = array();
    $location_default = -1;

    // get the Organizer ID's
    if ($event['group_id'] != '-1') {
        $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='{$event['group_id']}'";
        if (null === ($query = $database->query($SQL))) {
            $this->setError($database->get_error());
            return false;
        }
        if ($query->numRows() == 1) {
            // get the group settings
            $event_group = $query->fetchRow(MYSQL_ASSOC);
            if (!empty($event_group['kit_distribution_organizer'])) {
              // get all organizer ID's
              $ids = array();
              if (!$kitContactInterface->getContactsByCategory($event_group['kit_distribution_organizer'], $ids)) {
                  $this->setError($kitContactInterface->getError());
                  return false;
              }
              foreach ($ids as $id) {
                  $contact = array();
                  if (!$kitContactInterface->getContact($id, $contact))
                      // no error prompts within the loop
                      continue;
                  $organizer_ids[] = $contact;
                  // set the contact data for the organizer
                  if ($id == $event['organizer_id'])
                      $organizer_contact = $contact;
              }
            }

            if (!empty($event_group['kit_distribution_location'])) {
              // get all location ID's
              $ids = array();
              if (!$kitContactInterface->getContactsByCategory($event_group['kit_distribution_location'], $ids)) {
                $this->setError($kitContactInterface->getError());
                return false;
              }
              foreach ($ids as $id) {
                $contact = array();
                if (!$kitContactInterface->getContact($id, $contact))
                  // no error prompts within the loop
                  continue;
                $location_ids[] = $contact;
                // set the contact data for the location
                if ($id == $event['location_id'])
                  $location_contact = $contact;
              }
            }
        }
    }

    if (empty($organizer_contact) && ($event['organizer_id'] > 0)) {
      if (!$kitContactInterface->getContact($event['organizer_id'], $organizer_contact)) {
        $this->setError($kitContactInterface->getError());
        return false;
      }
      $organizer_default = $event['organizer_id'];
    }
    if (empty($location_contact) && ($event['location_id'] > 0)) {
      if (!$kitContactInterface->getContact($event['location_id'], $location_contact)) {
        $this->setError($kitContactInterface->getError());
        return false;
      }
      $location_default = $event['location_id'];
    }

    $fields = array(
        'id' => array(
            'name' => 'evt_id',
            'value' => $event_id
            ),
      'date_from' => array(
        'name' => 'evt_event_date_from',
        'id' => 'datepicker_1',
        'value' => (false !== ($x = strtotime($event['evt_event_date_from']))) ? date(CFG_DATE_STR, $x) : ''
      ),
      'date_to' => array(
        'name' => 'evt_event_date_to',
        'id' => 'datepicker_2',
        'value' => (false !== ($x = strtotime($event['evt_event_date_to']))) ? date(CFG_DATE_STR, $x) : ''
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
        'name' => 'evt_publish_date_from',
        'value' => (false !== ($x = strtotime($event['evt_publish_date_from']))) ? date(CFG_DATE_STR, $x) : '',
        'id' => 'datepicker_3'
      ),
      'publish_date_to' => array(
        'name' => 'evt_publish_date_to',
        'value' => (false !== ($x = strtotime($event['evt_publish_date_to']))) ? date(CFG_DATE_STR, $x) : '',
        'id' => 'datepicker_4'
      ),
      'participants_max' => array(
        'name' => 'evt_participants_max',
        'value' => $event['evt_participants_max']
      ),
      'participants_total' => array(
        'name' => 'evt_participants_total',
        'value' => $event['evt_participants_total']
      ),
      'deadline' => array(
        'name' => 'evt_deadline',
        'value' => (false !== ($x = strtotime($event['evt_deadline']))) ? date(CFG_DATE_STR, $x) : '',
        'id' => 'datepicker_5'
      ),
      'costs' => array(
        'name' => 'item_costs',
        'value' => sprintf(CFG_CURRENCY, number_format((float) $event['item_costs'], 2, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR))
      ),
      'group' => array(
        'name' => 'group_id',
        'value' => $group
      ),
      'status' => array(
        'name' => 'evt_status',
        'value' => $status
      ),
      'title' => array(
        'name' => 'item_title',
        'value' => $event['item_title']
      ),
      'short_description' => array(
        'name' => 'item_desc_short',
        'value' => self::unsanitizeText($event['item_desc_short'])
      ),
      'long_description' => array(
        'name' => 'item_desc_long',
        'value' => self::unsanitizeText($event['item_desc_long'])
      ),
      'free_field' => array(
          1 => array(
              'active' => (int) !empty(self::$cfgFreeFieldLabel_1),
              'use_html' => (int) self::$cfgFreeFieldUseHTML_1,
              'name' => 'item_free_1',
              'label' => self::$cfgFreeFieldLabel_1,
              'value' => self::unsanitizeText($event['item_free_1'])
              ),
          2 => array(
              'active' => (int) !empty(self::$cfgFreeFieldLabel_2),
              'use_html' => (int) self::$cfgFreeFieldUseHTML_2,
              'name' => 'item_free_2',
              'label' => self::$cfgFreeFieldLabel_2,
              'value' => self::unsanitizeText($event['item_free_2'])
              ),
          3 => array(
              'active' => (int) !empty(self::$cfgFreeFieldLabel_3),
              'use_html' => (int) self::$cfgFreeFieldUseHTML_3,
              'name' => 'item_free_3',
              'label' => self::$cfgFreeFieldLabel_3,
              'value' => self::unsanitizeText($event['item_free_3'])
              ),
          4 => array(
              'active' => (int) !empty(self::$cfgFreeFieldLabel_4),
              'use_html' => (int) self::$cfgFreeFieldUseHTML_4,
              'name' => 'item_free_4',
              'label' => self::$cfgFreeFieldLabel_4,
              'value' => self::unsanitizeText($event['item_free_4'])
              ),
          5 => array(
              'active' => (int) !empty(self::$cfgFreeFieldLabel_5),
              'use_html' => (int) self::$cfgFreeFieldUseHTML_5,
              'name' => 'item_free_5',
              'label' => self::$cfgFreeFieldLabel_5,
              'value' => self::unsanitizeText($event['item_free_5'])
              )
          ),
      'link' => array(
        'name' => 'item_desc_link',
        'value' => $event['item_desc_link']
      ),
      'perma_link' => array(
        'name' => 'evt_perma_link',
        'value' => $event['evt_perma_link'],
      ),
      'organizer' => array(
          'name' => 'organizer_id',
          'value' => $event['organizer_id'],
          'default' => $organizer_default,
          'contact' => $organizer_contact,
          'list' => $organizer_ids,
          'distribution' => $this->getDistributionValue($event_group['kit_distribution_organizer']),
          'count' => count($organizer_ids),
          ),
      'location' => array(
          'name' => 'location_id',
          'value' => $event['location_id'],
          'default' => $location_default,
          'contact' => $location_contact,
          'list' => $location_ids,
          'distribution' => $this->getDistributionValue($event_group['kit_distribution_location']),
          'count' => count($location_ids),
          'alias' => array(
              'name' => 'item_location',
              'value' => $event['item_location']
              ),
          'category' => array(
              'name' => 'item_category',
              'value' => self::unsanitizeText($event['item_category'])
              ),
          'link' => array(
              'name' => 'item_location_link',
              'value' => $event['item_location_link']
              )
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
      'form' => array(
          'name' => 'event_edit',
          'action' => self::$page_link
          ),
      'action' => array(
          'name' => self::REQUEST_ACTION,
          'value' => self::ACTION_EDIT_CHECK
          ),
      'language' => (LANGUAGE == 'EN') ? '' : strtolower(LANGUAGE),
      'item' => array(
          'name' => 'item_id',
          'value' => $item_id
          ),
      'suggestion' => array(
          'name' => self::REQUEST_SUGGESTION,
          'value' => -1
          ),
      'message' => array(
          'active' => (int) $this->isMessage(),
          'content' => $this->getMessage()
          ),
      'option' => array(
          'description' => array(
              'long' => (int) self::$cfgDescriptionLong,
              'short' => (int) self::$cfgDescriptionShort
              )
          ),
      'event' => $fields,
      'link_event_group' => sprintf('%s&%s', self::$page_link, http_build_query(array(
            self::REQUEST_ACTION => self::ACTION_SETTINGS,
            self::REQUEST_SUB_ACTION => self::ACTION_GROUP
            ))
          ),
        'link_options' => sprintf('%s&%s', self::$page_link, http_build_query(array(
            self::REQUEST_ACTION => self::ACTION_SETTINGS,
            self::REQUEST_SUB_ACTION => self::ACTION_CONFIG
            ))),
        'link_kit_id' => ADMIN_URL.'/admintools/tool.php?tool=kit&act=con&contact_id=',
        'link_kit_list' => ADMIN_URL.'/admintools/tool.php?tool=kit&act=list'
    );
    return $this->getTemplate('event.edit.dwoo', $data);
  } // dlgEditEvent()

  public function checkEditEvent() {
    global $manufakturConfig;
    global $database;
    global $kitContactInterface;

    $event_id = (isset($_REQUEST['evt_id']) && ($_REQUEST['evt_id'] > 0)) ? (int) $_REQUEST['evt_id'] : -1;
    $item_id = (isset($_REQUEST['item_id'])) && ($_REQUEST['item_id'] > 0) ? $_REQUEST['item_id'] : -1;

    $check_array = array(
      'evt_event_date_from',
      'evt_event_date_to',
      'evt_publish_date_from',
      'evt_publish_date_to',
      'evt_participants_max',
      'evt_deadline',
      'item_costs',
      'item_title',
      'item_desc_short'
    );
    // check request
    $checked = true;
    $message = '';
    $start_date_ok = false;
    $end_date_ok = false;

    foreach ($check_array as $request) {
      if (isset($_REQUEST[$request])) {
        switch ($request) :
          case 'evt_event_date_from' :
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
          case 'evt_event_date_to' :
            // check event date TO
            $x = strtotime($_REQUEST[$request]);
            if (!$x && $start_date_ok) {
              $x = strtotime($_REQUEST['evt_event_date_from']);
            }
            elseif (!$x) {
              $checked = false;
              $message .= $this->lang->translate('<p>The date {{ date }} for the field {{ field }} is invalid! Please type in the date in the format <i>mm-dd-YYYY</i>.</p>',
                  array('field' => $this->lang->translate('Date to'), 'date' => $_REQUEST[$request]));
              break;
            }
            // check time
            $_REQUEST[$request] = date('Y-m-d H:i:s', $x);
            $y = strtotime($_REQUEST['evt_event_date_from']);
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
          case 'evt_publish_date_from' :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST['evt_event_date_from']);
              if ($start_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) > mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
                $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
                $checked = false;
                break;
              }
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $x), date('d', $x) - 14, date('Y', $x)));
            }
            elseif ($start_date_ok) {
              $y = strtotime($_REQUEST['evt_event_date_from']);
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $y), date('d', $y) - 14, date('Y', $y)));
            }
            else {
              $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
              $checked = false;
            }
            break;
          case 'evt_publish_date_to' :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST['evt_event_date_to']);
              if ($end_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) < mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
                $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
                $checked = false;
                break;
              }
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23, 59, 59, date('n', $x), date('j', $x), date('Y', $x)));
            }
            elseif ($end_date_ok) {
              $y = strtotime($_REQUEST['evt_event_date_to']);
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m', $y), date('d', $y), date('Y', $y)));
            }
            else {
              $message .= $this->lang->translate('<p>Please check the publishing date!</p>');
              $checked = false;
            }
            break;
          case 'evt_participants_max' :
            $x = (int) $_REQUEST[$request];
            if ($x < 1) $x = -1;
            $_REQUEST[$request] = $x;
            break;
          case 'evt_participants_total' :
            $x = (int) $_REQUEST[$request];
            if ($x < 1) $x = 0;
            $_REQUEST[$request] = $x;
            break;
          case 'item_costs' :
            $x = (float) $_REQUEST[$request];
            if ($x < 1) $x = -1;
            $_REQUEST[$request] = $x;
            break;
          case 'evt_deadline' :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST['evt_event_date_from']);
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
              $_REQUEST[$request] = $_REQUEST['evt_event_date_from'];
            }
            else {
              $_REQUEST[$request] = '';
            }
            break;
          case 'item_title' :
            if (empty($_REQUEST[$request])) {
              $message .= $this->lang->translate('<p>Please insert a event title!</p>');
              $checked = false;
            }
            break;
          case 'item_desc_short' :
            if (empty($_REQUEST[$request]) && self::$cfgDescriptionShort) {
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
        'evt_deadline' => $_REQUEST['evt_deadline'],
        'evt_event_date_from' => $_REQUEST['evt_event_date_from'],
        'evt_event_date_to' => $_REQUEST['evt_event_date_to'],
        'group_id' => (int) $_REQUEST['group_id'],
        'evt_participants_max' => (int) $_REQUEST['evt_participants_max'],
        'evt_participants_total' => (int) $_REQUEST['evt_participants_total'],
        'evt_publish_date_from' => $_REQUEST['evt_publish_date_from'],
        'evt_publish_date_to' => $_REQUEST['evt_publish_date_to'],
        'evt_status' => $_REQUEST['evt_status'],
        'organizer_id' => (int) $_REQUEST['organizer_id'],
        'location_id' => (int) $_REQUEST['location_id']
      );
      $free_field = array();
      for ($i=1; $i<6; $i++) {
        if (isset($_REQUEST["item_free_$i"])) {
          $is_html = $manufakturConfig->getValue('cfg_event_free_field_'.$i.'_use_html', 'kit_event');
          $free_field[$i] = ($is_html) ? self::sanitizeText($_REQUEST["item_free_$i"]) : self::sanitizeText(strip_tags($_REQUEST["item_free_$i"]));
        }
        else
          $free_field[$i] = '';
      }

      if ((!isset($_REQUEST['item_location']) || empty($_REQUEST['item_location'])) && ($event['location_id'] > 0)) {
        $contact = array();
        if (!$kitContactInterface->getContact($event['location_id'], $contact)) {
          $this->setError($kitContactInterface->getError());
          return false;
        }
        $location = $contact['kit_identifier'];
      }
      else
        $location = self::sanitizeText($_REQUEST['item_location']);

      $item = array(
        'item_costs' => strip_tags($_REQUEST['item_costs']),
        'item_desc_link' => $_REQUEST['item_desc_link'],
        'item_desc_long' => self::$cfgDescriptionLong ? self::sanitizeText($_REQUEST['item_desc_long']) : '',
        'item_desc_short' => self::$cfgDescriptionShort ? self::sanitizeText($_REQUEST['item_desc_short']) : '',
        'item_location' => $location,
        'item_title' => strip_tags($_REQUEST['item_title']),
        'item_free_1' => self::sanitizeText($free_field[1]),
        'item_free_2' => self::sanitizeText($free_field[2]),
        'item_free_3' => self::sanitizeText($free_field[3]),
        'item_free_4' => self::sanitizeText($free_field[4]),
        'item_free_5' => self::sanitizeText($free_field[5]),
        'item_category' => self::sanitizeText($_REQUEST['item_category']),
        'item_location_link' => $_REQUEST['item_location_link']
      );

      if ($event_id == -1) {
        // neuer Datensatz
        $new_event = true;
        $fields = '';
        $values = '';
        $start = true;
        foreach ($item as $field => $value) {
          $fields .= (!$start) ? ",`$field`" : "`$field`";
          $values .= (!$start) ? ",'$value'" : "'$value'";
          $start = false;
        }
        $SQL = "INSERT INTO `".TABLE_PREFIX."mod_kit_event_item` ($fields) VALUES ($values)";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }
        // get the item ID
        $item_id = mysql_insert_id();
        // set the item ID
        $event['item_id'] = $item_id;

        $fields = '';
        $values = '';
        $start = true;
        foreach ($event as $field => $value) {
          $fields .= (!$start) ? ",`$field`" : "`$field`";
          $values .= (!$start) ? ",'$value'" : "'$value'";
          $start = false;
        }
        $SQL = "INSERT INTO `".TABLE_PREFIX."mod_kit_event` ($fields) VALUES ($values)";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }
        // get the item ID
        $event_id = mysql_insert_id();

        $message .= $this->lang->translate('<p>The event with the {{ id }} was successfull created.</p>', array('id' => $event_id));
      }
      else {
        // Datensatz aktualisieren
        $new_event = false;
        $items = '';
        $start = true;
        foreach ($item as $field => $value) {
          $items .= (!$start) ? ",`$field`='$value'" : "`$field`='$value'";
          $start = false;
        }
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event_item` SET $items WHERE `item_id`='$item_id'";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }
        $items = '';
        $start = true;
        foreach ($event as $field => $value) {
          $items .= (!$start) ? ",`$field`='$value'" : "`$field`='$value'";
          $start = false;
        }
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET $items WHERE `item_id`='$event_id'";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }

        $message .= $this->lang->translate('<p>The event with the ID {{ id }} was successfull updated.</p>',
            array('id' => $event_id));
      }
      // permaLink pruefen
      $this->checkPermaLink($event_id, $_REQUEST['evt_perma_link'], $new_event);
      if ($this->isError()) return false;
      if ($this->isMessage()) $message .= $this->getMessage();
      $this->clearMessage();
      unset($_REQUEST['evt_perma_link']);

      foreach (array_keys($event) as $key) {
        unset($_REQUEST[$key]);
      }
      foreach (array_keys($item) as $key) {
        unset($_REQUEST[$key]);
      }
      $_REQUEST['evt_id'] = $event_id;

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
    global $kitEventTools;
    global $database;

    if (!self::$cfgQRCodeCreate) return true;

    $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event` WHERE `evt_id`='$event_id'";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    if ($query->numRows() < 1) {
      $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id)));
      return false;
    }
    $event = $query->fetchRow(MYSQL_ASSOC);

    $c_type = self::$cfgQRCodeContent;

    if ($c_type == 2) {
      // iCal einlesen
      $dir = $kitEventTools->removeLeadingSlash(self::$cfgICalDir);
      $dir = $kitEventTools->addSlash($dir);
      $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
      $filename = $event['evt_ical_file'];
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
      if (empty($event['evt_perma_link'])) {
        $this->setMessage($this->lang->translate('<p>There is no permaLink defined!</p>'));
        return true;
      }
      $text = WB_URL . PAGES_DIRECTORY . $event['evt_perma_link'];
    }

    $level = self::$cfgQRCodeECLevel;
    $size = self::$cfgQRCodeSize;
    $margin = self::$cfgQRCodeMargin;

    $filename = sprintf('%s-%05d.png', date('Ymd-Hi', strtotime($event['evt_event_date_from'])), $event['evt_id']);
    $dir = $kitEventTools->removeLeadingSlash(self::$cfgQRCodeDir);
    $dir = $kitEventTools->addSlash($dir);
    $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
    if (!file_exists($dir_path)) {
      if (!mkdir($dir_path, 0755)) {
        $this->setError($this->lang->translate('Error: cannot create the directory {{ directory }}!', array('directory' => $dir_path)));
        return false;
      }
    }

    $QRCode = new QRcode();
    $QRCode->png($text, $dir_path . $filename, $level, $size, $margin);

    $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_qrcode_image`='$filename' WHERE `evt_id`='{$event['evt_id']}'";
    if (null === $database->query($SQL)) {
      $this->setError($database->get_error());
      return false;
    }

    return true;
  } // createQRCodeFile()

  public function createICalFile($event_id) {
    global $kitEventTools;
    global $database;

    if (!self::$cfgICalCreate) {
      // keine iCal Dateien anlegen
      return true;
    }
    $tke = TABLE_PREFIX.'mod_kit_event';
    $tkei = TABLE_PREFIX.'mod_kit_event_item';
    $SQL = "SELECT * FROM `$tke`, `$tkei` WHERE $tke.item_id=$tkei.item_id AND $tke.evt_id='$event_id'";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    if ($query->numRows() < 1) {
      $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!',
          array('id' => $event_id)));
      return false;
    }
    $event = $query->fetchRow(MYSQL_ASSOC);
    // iCal initialisieren und schreiben
    $desc = utf8_fast_entities_to_umlauts(strip_tags($event['item_desc_long']));

    $vCal = new vcalendar(array(
      'unique_id' => 'kitEvent',
      'language' => strtolower(LANGUAGE)
    ));
    $evt = &$vCal->newComponent('vevent');
    $evt->setProperty('class', 'PUBLIC'); // PUBLIC = Standard
    $evt->setProperty('priority', 0); // 0 = keine Angabe
    $evt->setProperty('status', 'CONFIRMED'); // TENTATIVE, CONFIRMED, CANCELLED
    $evt->setProperty('summary', $event['item_title']);
    $evt->setProperty('description', $desc);
    list($year, $month, $day, $hour, $minute, $second) = explode('-', date('Y-m-d-H-i-s', strtotime($event['evt_event_date_from'])));
    $evt->setProperty('dtstart', $year, $month, $day, $hour, $minute, $second);
    list($year, $month, $day, $hour, $minute, $second) = explode('-', date('Y-m-d-H-i-s', strtotime($event['evt_event_date_to'])));
    $evt->setProperty('dtend', $year, $month, $day, $hour, $minute, $second);
    $evt->setProperty('location', $event['item_location']);
    $ical = $vCal->createCalendar();
    $filename = sprintf('%s-%05d.ics', date('Ymd-Hi', strtotime($event['evt_event_date_from'])), $event['evt_id']);
    $dir = $kitEventTools->removeLeadingSlash(self::$cfgICalDir);
    $dir = $kitEventTools->addSlash($dir);
    $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
    if (!file_exists($dir_path)) {
      if (!mkdir($dir_path, 0755)) {
        $this->setError($this->lang->translate('Error: cannot create the directory {{ directory }}!',
          array('directory' => $dir_path)));
        return false;
      }
    }
    if (!file_put_contents($dir_path . $filename, $ical)) {
      $this->setError($this->lang->translate('Error: cannot create the file {{ file }}!',
          array('file' => $dir . $filename)));
      return false;
    }
    $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_ical_file`='$filename' WHERE `evt_id`='{$event['evt_id']}'";
    if (null === $database->query($SQL)) {
      $this->setError($database->get_error());
      return false;
    }
    return true;
  } // createICalFile()

  public function checkPermaLink($event_id, $perma_link, $new_event = false) {
    global $database;

    if (!self::$cfgPermaLinkCreate)
      return true;

    $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event` WHERE `evt_id`='$event_id'";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    if ($query->numRows() < 1) {
      $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event_id)));
      return false;
    }
    $event = $query->fetchRow(MYSQL_ASSOC);

    if ($new_event) {
      if (empty($perma_link)) {
        if ($event['group_id'] == -1) return true;
        // pruefen ob ein Pattern angegeben ist
        $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='{$event['group_id']}'";
        if (null === ($query = $database->query($SQL))) {
          $this->setError($database->get_error());
          return false;
        }
        if ($query->numRows() < 1) {
          $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event['group_id'])));
          return false;
        }
        $group = $query->fetchRow(MYSQL_ASSOC);

        $pattern = $group['group_perma_pattern'];
        $redirect = $group['group_redirect_page'];
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect,
            (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
                self::REQUEST_ACTION => self::ACTION_EVENT,
                self::REQUEST_EVENT => self::VIEW_ID,
                self::REQUEST_EVENT_DETAIL => '1',
                self::REQUEST_EVENT_ID => $event['evt_id']
                )));
        // kein Muster und Redirect definiert, kein permaLink gesetzt - nix zu tun
        if (empty($pattern) || empty($redirect))
          return true;
        // Pattern aktivieren
        $date = getdate(strtotime($event['evt_event_date_from']));
        $pattern_array = array('{$YEAR}','{$MONTH}','{$DAY}','{$ID}','{$EXT}','{$NAME}');
        $values_array = array(
          $date['year'] - 2000,
          sprintf('%02d', $date['mon']),
          sprintf('%02d', $date['mday']),
          $event['evt_id'],
          PAGE_EXTENSION,
          $group['group_name']
        );

        $perma_link = str_ireplace($pattern_array, $values_array, $pattern);
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL.PAGES_DIRECTORY.$redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError($permaLink->getError());
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        // dbEvent aktualisieren
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_perma_link`='$perma_link' WHERE `evt_id`='{$event['evt_id']}'";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }
        $this->setMessage($this->lang->translate('<p>The permaLink {{ link }} was created!</p>', array('link' => $perma_link)));
        return true;
      }
      else {
        // permaLink ist von Hand gesetzt
        if ($event['group_id'] == -1)
          return true;
        $SQL = "SELECT `group_redirect_page` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='{$event['group_id']}'";
        $redirect = $database->get_one($SQL, MYSQL_ASSOC);
        if ($database->is_error()) {
          $this->setError($database->get_error());
          return false;
        }
        if (is_null($redirect)) {
          $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event['group_id'])));
          return false;
        }

        if (empty($redirect)) {
          $this->setMessage($this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>'));
          return false;
        }
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect, (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
          self::REQUEST_ACTION => self::ACTION_EVENT,
          self::REQUEST_EVENT => self::VIEW_ID,
          self::REQUEST_EVENT_DETAIL => '1',
          self::REQUEST_EVENT_ID => $event['evt_id']
        )));
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL.PAGES_DIRECTORY.$redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError($permaLink->getError());
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        // dbEvent aktualisieren
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_perma_link`='$perma_link' WHERE `evt_id`='{$event['evt_id']}'";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }
        $this->setMessage($this->lang->translate('<p>The permaLink {{ link }} was created!</p>', array('link' => $perma_link)));
        return true;
      }
    }
    elseif ($event['evt_perma_link'] != $perma_link) {
      // der permaLink wurde geaendert...
      if (empty($event['evt_perma_link'])) {
        // der permaLink ist neu
        if ($event['group_id'] == -1) {
          $this->setMessage($this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>'));
          return false;
        }
        $SQL = "SELECT `group_redirect_page` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='{$event['group_id']}'";
        $redirect = $database->get_one($SQL, MYSQL_ASSOC);
        if ($database->is_error()) {
          $this->setError($database->get_error());
          return false;
        }
        if (is_null($redirect)) {
          $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event['group_id'])));
          return false;
        }

        if (empty($redirect)) {
          $this->setMessage($this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>'));
          return false;
        }
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect, (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
          self::REQUEST_ACTION => self::ACTION_EVENT,
          self::REQUEST_EVENT => self::VIEW_ID,
          self::REQUEST_EVENT_DETAIL => '1',
          self::REQUEST_EVENT_ID => $event['evt_id']
        )));
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL.PAGES_DIRECTORY.$redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError($permaLink->getError());
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        // dbEvent aktualisieren
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_perma_link`='$perma_link' WHERE `evt_id`='{$event['evt_id']}'";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
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
        if (!$permaLink->deletePermaLink($event['evt_perma_link'])) {
          if ($permaLink->isError()) {
            $this->setError(sprintf($permaLink->getError()));
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_perma_link`='' WHERE `evt_id`='{$event['evt_id']}'";
        if (null === $database->query($SQL)) {
          $this->setError($database->get_error());
          return false;
        }

        $message = $this->lang->translate('<p>The permaLink {{ link }} was deleted!</p>', array('link' => $event['evt_perma_link']));
        if (empty($perma_link)) {
          // permaLink wird nur geloescht, kein neuer angelegt...
          $this->setMessage($message);
          return true;
        }
        // neuen permaLink anlegen
        if ($event['group_id'] == -1) {
          $message .= $this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>');
          $this->setMessage($message);
          return false;
        }
        $SQL = "SELECT `group_redirect_page` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='{$event['group_id']}'";
        $redirect = $database->get_one($SQL, MYSQL_ASSOC);
        if ($database->is_error()) {
          $this->setError($database->get_error());
          return false;
        }
        if (is_null($redirect)) {
          $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $event['group_id'])));
          return false;
        }

        if (empty($redirect)) {
          $this->setMessage($this->lang->translate('<p>To create a permaLink for this event, you must select a valid event group!</p>'));
          return false;
        }
        // REDIRECT mit den erforderlichen Parametern ergaenzen
        $redirect = sprintf('%s%s%s', $redirect, (strpos($redirect, '?') !== false) ? '&' : '?', http_build_query(array(
            self::REQUEST_ACTION => self::ACTION_EVENT,
            self::REQUEST_EVENT => self::VIEW_ID,
            self::REQUEST_EVENT_DETAIL => '1',
            self::REQUEST_EVENT_ID => $event['evt_id']
        )));
        $permaLink = new permaLink();
        $pid = -1;
        if (!$permaLink->createPermaLink(WB_URL.PAGES_DIRECTORY.$redirect, $perma_link, 'kitEvent', dbPermaLink::type_addon, $pid, permaLink::use_request)) {
          if ($permaLink->isError()) {
            $this->setError($permaLink->getError());
            return false;
          }
          $this->setMessage($permaLink->getMessage());
          return false;
        }
        // dbEvent aktualisieren
        $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event` SET `evt_perma_link`='$perma_link' WHERE `evt_id`='{$event['evt_id']}'";
            if (null === $database->query($SQL)) {
                $this->setError($database->get_error());
                return false;
        }
        $this->setMessage($this->lang->translate('<p>The permaLink {{ link }} was created!</p>', array('link' => $perma_link)));
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
    global $database;

    $group_id = (isset($_REQUEST['group_id']) && ($_REQUEST['group_id'] > 0)) ? $_REQUEST['group_id'] : -1;

    // get active event group
    if ($group_id > 0) {
      $SQL = "SELECT * FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_id`='$group_id'";
      if (null === ($query = $database->query($SQL)))  {
        $this->setError($database->get_error());
        return false;
      }
      if ($query->numRows() < 1) {
        $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $group_id)));
        return false;
      }
      $active_group = $query->fetchRow(MYSQL_ASSOC);
    }
    else {
      // new group
      $SQL = "SHOW FIELDS FROM `".TABLE_PREFIX."mod_kit_event_group`";
      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }
      $active_group = array();
      while (false !== ($field = $query->fetchRow(MYSQL_ASSOC)))
        $active_group[$field['Field']] = '';
      $active_group['group_status'] = 1;
    }

    // get all groups
    $SQL = "SELECT `group_id`, `group_name` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_status`!='-1' ORDER BY `group_name` ASC";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    // event groups
    $grps = array();
    $grps[] = array(
      'selected' => ($group_id == -1) ? 1 : 0,
      'value' => -1,
      'text' => $this->lang->translate('- create a new group -')
    );

    while (false !== ($grp = $query->fetchRow(MYSQL_ASSOC))) {
      $grps[] = array(
        'selected' => ($grp['group_id'] == $group_id) ? 1 : 0,
        'value' => $grp['group_id'],
        'text' => $grp['group_name']
      );
    }

    $distribution_array = array();

    $SQL = "SELECT `array_identifier`, `array_value` FROM `".TABLE_PREFIX."mod_kit_contact_array_cfg` ".
      "WHERE `array_type`='typeDistribution' AND `array_status`='statusActive' ORDER BY `array_value` ASC";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    while (false !== ($dist = $query->fetchRow(MYSQL_ASSOC))) {
      $distribution_array[] = array(
          'value' => $dist['array_identifier'],
          'text' => $dist['array_value']
          );
    }

    // group status
    $status = array(
        'ACTIVE' => array(
            'selected' => ($active_group['group_status'] == 1) ? 1 : 0,
            'value' => 1,
            'text' => 'ACTIVE'
        ),
        'LOCKED' => array(
            'selected' => ($active_group['group_status'] == 0) ? 1 : 0,
            'value' => 0,
            'text' => 'LOCKED'
        ),
        'DELETED' => array(
            'selected' => ($active_group['group_status'] == -1) ? 1 : 0,
            'value' => -1,
            'text' => 'DELETED'
        )
    );


    // REDIRECT URLs Array erstellen
    $SQL = "SELECT `link` FROM `".TABLE_PREFIX."pages` ORDER BY `link` ASC";
    if (null === ($pages = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    $page_array = array();
    $page_array[] = array(
      'value' => -1,
      'text' => $this->lang->translate('- select the redirect page -')
    );
    while (false !== ($page = $pages->fetchRow(MYSQL_ASSOC))) {
      $page_array[] = array(
        'value' => $page['link'] . PAGE_EXTENSION,
        'text' => $page['link'] . PAGE_EXTENSION
      );
    }

    $group = array(
      'group' => array(
        'name' => 'group_id',
        'value' => $grps,
        'location' => sprintf('javascript:execOnChange(\'%s\', \'%s\');', sprintf('%s&amp;%s=%s&amp;%s=%s%s&amp;%s=',
            self::$page_link,
            self::REQUEST_ACTION,
            self::ACTION_SETTINGS,
            self::REQUEST_SUB_ACTION,
            self::ACTION_GROUP,
            (defined('LEPTON_VERSION') && isset($_GET['leptoken'])) ? sprintf('&amp;leptoken=%s', $_GET['leptoken']) : '',
            'group_id'), 'group_id'),
      ),
      'name' => array(
        'name' => 'group_name',
        'value' => $active_group['group_name'],
      ),
      'desc' => array(
        'name' => 'group_desc',
        'value' => $active_group['group_desc'],
      ),
      'status' => array(
        'name' => 'group_status',
        'value' => $status,
      ),
      'perma_pattern' => array(
        'name' => 'group_perma_pattern',
        'value' => $active_group['group_perma_pattern'],
      ),
      'redirect_page' => array(
        'name' => 'group_redirect_page',
        'value' => $active_group['group_redirect_page'],
        'options' => $page_array,
        ),
      'distribution_participant' => array(
          'name' => 'kit_distribution_participant',
          'value' => $active_group['kit_distribution_participant'],
          'options' => $distribution_array
          ),
      'distribution_organizer' => array(
          'name' => 'kit_distribution_organizer',
          'value' => $active_group['kit_distribution_organizer'],
          'options' => $distribution_array
          ),
      'distribution_location' => array(
          'name' => 'kit_distribution_location',
          'value' => $active_group['kit_distribution_location'],
          'options' => $distribution_array
          )
    );

    $data = array(
      'form' => array(
          'name' => 'event_group',
          'action' => self::$page_link
          ),
      'action' => array(
          'name' => self::REQUEST_ACTION,
          'value' => self::ACTION_SETTINGS
          ),
      'sub_action' => array(
          'name' => self::REQUEST_SUB_ACTION,
          'value' => self::ACTION_GROUP_CHECK
          ),
      'message' => array(
          'active' => (int) $this->isMessage(),
          'content' => $this->getMessage()
          ),
      'group' => $group,

    );
    return $this->getTemplate('configuration.group.dwoo', $data);
  } // dlgEditGroup()

  /**
   * check the edited event group and create a new group
   * or update the desired group
   *
   * @return STR dlgEditGroup()
   */
  public function checkEditGroup() {
    global $database;

    $group_id = $_REQUEST['group_id'];

    if (empty($_REQUEST['group_name'])) {
      $this->setMessage($this->lang->translate('<p>The event group must be named!</p>'));
      return $this->dlgEditGroup();
    }

    $data = array(
      'group_name' => self::sanitizeText($_REQUEST['group_name']),
      'group_desc' => self::sanitizeText($_REQUEST['group_desc']),
      'group_status' => $_REQUEST['group_status'],
      'group_perma_pattern' => $_REQUEST['group_perma_pattern'],
      'group_redirect_page' => ($_REQUEST['group_redirect_page'] == -1) ? '' : $_REQUEST['group_redirect_page'],
      'kit_distribution_participant' => isset($_REQUEST['kit_distribution_participant']) ? $_REQUEST['kit_distribution_participant'] : '',
      'kit_distribution_location' => isset($_REQUEST['kit_distribution_location']) ? $_REQUEST['kit_distribution_location'] : '',
      'kit_distribution_organizer' => isset($_REQUEST['kit_distribution_organizer']) ? $_REQUEST['kit_distribution_organizer'] : '',
    );

    if ($group_id > 0) {
      // existing group
      $items = '';
      $start = true;
      foreach ($data as $field => $value) {
        $items .= (!$start) ? ",`$field`='$value'" : "`$field`='$value'";
        $start = false;
      }
      $SQL = "UPDATE `".TABLE_PREFIX."mod_kit_event_group` SET $items WHERE `group_id`='$group_id'";
      if (null === $database->query($SQL)) {
        $this->setError($database->get_error());
        return false;
      }
      $this->setMessage($this->lang->translate('<p>The event group with the ID {{ id }} was successfull updated</p>', array('id' => $group_id)));
    }
    else {
      // new group - check if name is already in use
      $SQL = "SELECT `group_id` FROM `".TABLE_PREFIX."mod_kit_event_group` WHERE `group_name`='{$data['group_name']}' AND `group_status`!='-1'";
      if (null === ($query = $database->query($SQL))) {
        $this->setError($database->get_error());
        return false;
      }
      if ($query->numRows() > 0) {
        $this->setMessage($this->lang->translate('<p>The event group with the name {{ name }} already exists!</p>',
            array('name' => $data['group_name'])));
        return $this->dlgEditGroup();
      }
      $fields = '';
      $values = '';
      $start = true;
      foreach ($data as $field => $value) {
        $fields .= (!$start) ? ",`$field`" : "`$field`";
        $values .= (!$start) ? ",'$value'" : "'$value'";
        $start = false;
      }
      $SQL = "INSERT INTO `".TABLE_PREFIX."mod_kit_event_group` ($fields) VALUES ($values)";
      if (null === $database->query($SQL)) {
        $this->setError($database->get_error());
        return false;
      }
      $group_id = mysql_insert_id();
      $this->setMessage($this->lang->translate('<p>The event group with the ID {{ id }} was successfull created.</p>',
          array('id' => $group_id)));
    }
    return $this->dlgEditGroup();
  } // checkEditGroup()

  public function dlgAbout() {
    $notes = file_get_contents(LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/CHANGELOG');
    $use_markdown = 0;
    if (file_exists(LEPTON_PATH.'/modules/lib_markdown/standard/markdown.php')) {
      require_once LEPTON_PATH.'/modules/lib_markdown/standard/markdown.php';
      $notes = Markdown($notes);
      $use_markdown = 1;
    }
    $data = array(
        'version' => sprintf('%01.2f', $this->getVersion()),
        'img_url' => self::$img_url . '/kit_event_logo_424x283.jpg',
        'release' => array(
            'use_markdown' => $use_markdown,
            'notes' => $notes
        )
    );
    return $this->getTemplate('about.dwoo', $data);
  } // dlgAbout()

  protected function actionDelete() {
    global $database;

    if (!isset($_REQUEST['ord_id'])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => -1))));
      return false;
    }
    $event_id = (int) $_REQUEST['ord_id'];
    $SQL = "DELETE FROM `".TABLE_PREFIX."mod_kit_event_order` WHERE `ord_id`='$event_id'";
    $database->query($SQL);
    if ($database->is_error()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    $this->setMessage($this->lang->translate('The message with the ID {{ id }} was successfull deleted.',
        array('id' => $_REQUEST['ord_id'])));
    return $this->dlgMessages();
  } // actionDelete()

  public function dlgMessages() {
    global $database;
    global $kitContactInterface;

    $tke = TABLE_PREFIX.'mod_kit_event';
    $tkeo = TABLE_PREFIX.'mod_kit_event_order';
    $tkei = TABLE_PREFIX.'mod_kit_event_item';
    $SQL = "SELECT * FROM `$tke`, `$tkeo`, `$tkei` WHERE $tke.evt_id=$tkeo.evt_id AND $tke.item_id=$tkei.item_id ORDER BY `ord_date` LIMIT 100";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }

    $items = '';
    $rows = array();
    while (false !== ($message = $query->fetchRow(MYSQL_ASSOC))) {
      $contact = array();
      if (!$kitContactInterface->getContact($message['kit_id'], $contact)) {
        $this->setError($kitContactInterface->getError());
        return false;
      }
      $dt = strtotime($message['ord_confirm']);
      $declared = (checkdate(date('n', $dt), date('j', $dt), date('Y', $dt))) ? $this->lang->translate('Yes') : '';
      $name = sprintf('%s, %s', $contact['kit_last_name'], $contact['kit_first_name']);
      if (strlen($name) < 3) $name = '';
      $rows[$message['ord_id']] = array(
          'delete' => array(
              'link' => sprintf('%s&%s', self::$page_link, http_build_query(array(
                  self::REQUEST_ACTION => self::ACTION_DELETE,
                  'ord_id' => $message['ord_id']
                  ))),
              ),
          'contact' => $contact,
          'detail' => array(
              'link' => sprintf('%s&%s',
                  self::$page_link, http_build_query(array(
                      self::REQUEST_ACTION => self::ACTION_MESSAGES_DETAIL,
                      'ord_id' => $message['ord_id']
                      )))
              ),
          'order' => array(
              'id' => $message['ord_id'],
              'confirmed' => (checkdate(date('n', $dt), date('j', $dt), date('Y', $dt))) ? 1 : 0,
              'message' => self::unsanitizeText($message['ord_message']),
              'fields' => $message
              ),
          'event' => array(
              'title' => $message['item_title'],
              'date' => $message['evt_event_date_from']
              ),
      );
    }

    // check if libraryAdmin exists
    if (file_exists(WB_PATH.'/modules/libraryadmin/inc/class.LABackend.php')) {
      require_once WB_PATH.'/modules/libraryadmin/inc/class.LABackend.php';
      // create instance; if you're not using OOP, use a simple var, like $la
      $libraryAdmin = new LABackend();
      // load the preset
      $libraryAdmin->loadPreset(array(
          'module' => 'kit_event',
          'lib'    => 'lib_jquery',
          'preset' => 'dataTable'
      ));
      // print the preset
      $libraryAdmin->printPreset();
    }

    $data = array(
      'orders' => $rows,
      'message' => array(
          'active' => (int) $this->isMessage(),
          'text' => $this->getMessage()
          )
    );
    return $this->getTemplate('order.list.dwoo', $data);
  } // dlgMessages()

  public function dlgMessageDetail() {
    global $database;
    global $kitContactInterface;
    global $manufakturConfig;

    $order_id = (isset($_REQUEST['ord_id'])) ? (int) $_REQUEST['ord_id'] : -1;

    $tke = TABLE_PREFIX.'mod_kit_event';
    $tkeo = TABLE_PREFIX.'mod_kit_event_order';
    $tkei = TABLE_PREFIX.'mod_kit_event_item';

    $SQL = "SELECT * FROM `$tke`, `$tkeo`, `$tkei` WHERE $tke.evt_id=$tkeo.evt_id AND $tke.item_id=$tkei.item_id AND `ord_id`='$order_id'";
    if (null === ($query = $database->query($SQL))) {
      $this->setError($database->get_error());
      return false;
    }
    if ($query->numRows() < 1) {
      $this->setError($this->lang->translate('Error: The id {{ id }} is invalid!', array('id' => $order_id)));
      return false;
    }
    $detail = $query->fetchRow(MYSQL_ASSOC);

    $contact = array();
    if (!$kitContactInterface->getContact($detail['kit_id'], $contact, true)) {
      $this->setError($kitContactInterface->getError());
      return false;
    }

    $dt = strtotime($detail['ord_confirm']);
    $declared = (checkdate(date('n', $dt), date('j', $dt), date('Y', $dt))) ? date(CFG_DATETIME_STR, $dt) : $this->lang->translate('No');

    $data = array(
        'contact' => $contact,
        'kit_link' => sprintf('%s&%s', ADMIN_URL.'/admintools/tool.php?tool=kit&act=con', http_build_query(array(
            'contact_id' => $detail['kit_id']
            ))),
        'event' => array(
            'title' => $detail['item_title'],
            'date' => $detail['evt_event_date_from'],
            'link' => sprintf('%s&%s', self::$page_link, http_build_query(array(
                self::REQUEST_ACTION => self::ACTION_EDIT,
                'evt_id' => $detail['evt_id']
                )))
            ),
        'information' => array(
            'best_time' => $detail['ord_best_time'],
            'declared' => $declared,
            'message' => $detail['ord_message'],
            ),
        'free_field' => array(
            1 => array(
                'label' => $manufakturConfig->getValue('cfg_event_free_field_1', 'kit_event'),
                'value' => $detail['ord_free_1']
                ),
            2 => array(
                'label' => $manufakturConfig->getValue('cfg_event_free_field_2', 'kit_event'),
                'value' => $detail['ord_free_2']
                ),
            3 => array(
                'label' => $manufakturConfig->getValue('cfg_event_free_field_3', 'kit_event'),
                'value' => $detail['ord_free_3']
                ),
            4 => array(
                'label' => $manufakturConfig->getValue('cfg_event_free_field_4', 'kit_event'),
                'value' => $detail['ord_free_4']
                ),
            5 => array(
                'label' => $manufakturConfig->getValue('cfg_event_free_field_5', 'kit_event'),
                'value' => $detail['ord_free_5']
                )
            ),
        'back_link' => sprintf('%s&%s=%s', self::$page_link, self::REQUEST_ACTION, self::ACTION_MESSAGES)
    );
    return $this->getTemplate('order.detail.dwoo', $data);
  } // dlgMessageDetail()

  protected function actionConfiguration() {
    $sub_action = isset($_REQUEST[self::REQUEST_SUB_ACTION]) ? $_REQUEST[self::REQUEST_SUB_ACTION] : self::ACTION_CONFIG;
    // select the navigation tab
    $nav_key = $sub_action;

    switch ($sub_action):
    case self::ACTION_GROUP:
      $result = $this->dlgEditGroup();
      break;
    case self::ACTION_GROUP_CHECK:
      $nav_key = self::ACTION_GROUP;
      $result = $this->checkEditGroup();
      break;
    case self::ACTION_CONFIG:
    default:
      $result = $this->dlgConfig();
      break;
    endswitch;

    $sub_navigation = array(
        self::ACTION_CONFIG => 'General',
        self::ACTION_GROUP => 'Groups'
        );

    $navigation = array();
    foreach ($sub_navigation as $key => $value) {
      $navigation[] = array(
          'active' => ($key == $nav_key) ? 1 : 0,
          'url' => sprintf('%s&%s', self::$page_link, http_build_query(array(
              self::REQUEST_ACTION => self::ACTION_SETTINGS,
              self::REQUEST_SUB_ACTION => $key))),
          'text' => $value);
    }
    // complete the output
    $data = array(
        'navigation' => $navigation,
        'content' => $result
    );
    return $this->getTemplate('configuration.dwoo', $data);
  } // actionConfiguration()

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
            self::REQUEST_ACTION => self::ACTION_SETTINGS,
            self::REQUEST_SUB_ACTION => self::ACTION_CONFIG
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