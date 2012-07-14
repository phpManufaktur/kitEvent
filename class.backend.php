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
require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/class.editor.php');
require_once (WB_PATH . '/modules/perma_link/class.interface.php');
require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/include/ical/iCalcreator.class.php');
require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/include/qrcode/qrlib.php');
require_once (WB_PATH . '/framework/functions-utf8.php');

class eventBackend {
  const request_action = 'act';
  const request_items = 'its';
  const request_time_start = 'ets';
  const request_time_end = 'ete';
  const request_suggestion = 'sgg';
  const request_show_all = 'sa';
  const action_about = 'abt';
  const action_config = 'cfg';
  const action_config_check = 'cfgc';
  const action_default = 'def';
  const action_edit = 'edt';
  const action_edit_check = 'edtc';
  const action_group = 'grp';
  const action_group_check = 'grpc';
  const action_list = 'lst';
  const action_messages = 'msg';
  const action_messages_detail = 'msgd';
  private $tab_navigation_array = array(
    self::action_list => event_tab_list,
    self::action_edit => event_tab_edit,
    self::action_group => event_tab_group,
    self::action_messages => event_tab_messages,
    self::action_config => event_tab_config,
    self::action_about => event_tab_about
  );
  private $page_link = '';
  private $img_url = '';
  private $template_path = '';
  private $error = '';
  private $message = '';

  public function __construct() {
    $this->page_link = ADMIN_URL . '/admintools/tool.php?tool=kit_event';
    $this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/htt/';
    $this->img_url = WB_URL . '/modules/' . basename(dirname(__FILE__)) . '/images/';
    date_default_timezone_set(event_cfg_time_zone);
  } // __construct()

  /**
   * Set $this->error to $error
   *
   * @param STR $error
   */
  public function setError($error) {
    $caller = next(debug_backtrace());
    $this->error = sprintf('[%s::%s - %s] %s', basename($caller['file']), $caller['function'], $caller['line'], $error);
  } // setError()

  /**
   * Get Error from $this->error;
   *
   * @return STR $this->error
   */
  public function getError() {
    return $this->error;
  } // getError()

  /**
   * Check if $this->error is empty
   *
   * @return BOOL
   */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Reset Error to empty String
   */
  public function clearError() {
    $this->error = '';
  }

  /**
   * Set $this->message to $message
   *
   * @param STR $message
   */
  public function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
   * Get Message from $this->message;
   *
   * @return STR $this->message
   */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
   * Check if $this->message is empty
   *
   * @return BOOL
   */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage
  public function clearMessage() {
    $this->message = '';
  } // clearMessage()

  /**
   * Return Version of Module
   *
   * @return FLOAT
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
  public function getTemplate($template, $template_data) {
    global $parser;
    try {
      $result = $parser->get($this->template_path . $template, $template_data);
    } catch (Exception $e) {
      $this->setError(sprintf(event_error_template_error, $template, $e->getMessage()));
      return false;
    }
    return $result;
  } // getTemplate()

  /**
   * Verhindert XSS Cross Site Scripting
   *
   * @param REFERENCE $_REQUEST
   *          Array
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
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
    switch ($action) :
      case self::action_about :
        $this->show(self::action_about, $this->dlgAbout());
        break;
      case self::action_config :
        $this->show(self::action_config, $this->dlgConfig());
        break;
      case self::action_config_check :
        $this->show(self::action_config, $this->checkConfig());
        break;
      case self::action_edit :
        $this->show(self::action_edit, $this->dlgEditEvent());
        break;
      case self::action_edit_check :
        $this->show(self::action_edit, $this->checkEditEvent());
        break;
      case self::action_group :
        $this->show(self::action_group, $this->dlgEditGroup());
        break;
      case self::action_group_check :
        $this->show(self::action_group, $this->checkEditGroup());
        break;
      case self::action_messages :
        $this->show(self::action_messages, $this->dlgMessages());
        break;
      case self::action_messages_detail :
        $this->show(self::action_messages, $this->dlgMessageDetail());
        break;
      case self::action_list :
      default :
        $this->show(self::action_list, $this->dlgList());
        break;
    endswitch
    ;
  } // action

  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   *
   * @param $action -
   *          aktives Navigationselement
   * @param $content -
   *          Inhalt
   *
   * @return ECHO RESULT
   */
  public function show($action, $content) {
    $navigation = array();
    foreach ($this->tab_navigation_array as $key => $value) {
      $navigation[] = array(
        'active' => ($key == $action) ? 1 : 0,
        'url' => sprintf('%s&%s=%s', $this->page_link, self::request_action, $key),
        'text' => $value
      );
    }
    $data = array(
      'WB_URL' => WB_URL,
      'navigation' => $navigation,
      'error' => ($this->isError()) ? 1 : 0,
      'content' => ($this->isError()) ? $this->getError() : $content
    );
    echo $this->getTemplate('backend.body.htt', $data);
  } // show()
  public function dlgList() {
    global $dbEvent;
    global $dbEventItem;
    global $dbEventGroup;
    global $parser;

    if (isset($_REQUEST[self::request_show_all]) && ($_REQUEST[self::request_show_all] == 1)) {
      $SQL = sprintf("SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s!='%s' ORDER BY %s ASC", $dbEvent->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, dbEvent::field_status, dbEvent::status_deleted, dbEvent::field_event_date_from);
      $this->setMessage(event_msg_show_all_events);
    }
    else {
      $start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
      $SQL = sprintf("SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s!='%s' AND %s>='%s' ORDER BY %s ASC", $dbEvent->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, dbEvent::field_status, dbEvent::status_deleted, dbEvent::field_event_date_from, $start_date, dbEvent::field_event_date_from);
    }
    $events = array();
    if (!$dbEvent->sqlExec($SQL, $events)) {
      $this->setError($dbEvent->getError());
      return false;
    }

    $th = array(
      array(
        'class' => dbEvent::field_id,
        'text' => event_th_id
      ),
      array(
        'class' => dbEvent::field_event_date_from,
        'text' => event_th_date_from
      ),
      array(
        'class' => dbEvent::field_event_date_to,
        'text' => event_th_date_to
      ),
      array(
        'class' => dbEvent::field_event_group,
        'text' => event_th_group
      ),
      array(
        'class' => dbEvent::field_participants_max,
        'text' => event_th_participants_max
      ),
      array(
        'class' => dbEvent::field_participants_total,
        'text' => event_th_participants_total
      ),
      array(
        'class' => dbEvent::field_deadline,
        'text' => event_th_deadline
      ),
      array(
        'class' => dbEventItem::field_title,
        'text' => event_th_title
      )
    );

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
        'id_link' => sprintf('%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_edit, dbEvent::field_id, $event[dbEvent::field_id]),
        'id' => sprintf('%04d', $event[dbEvent::field_id]),
        'date_from_name' => dbEvent::field_event_date_from,
        'date_from' => date(event_cfg_datetime_str, strtotime($event[dbEvent::field_event_date_from])),
        'date_to_name' => dbEvent::field_event_date_to,
        'date_to' => date(event_cfg_datetime_str, strtotime($event[dbEvent::field_event_date_to])),
        'group_name' => dbEvent::field_event_group,
        'group' => $grp,
        'part_max_name' => dbEvent::field_participants_max,
        'part_max' => $event[dbEvent::field_participants_max],
        'part_total_name' => dbEvent::field_participants_total,
        'part_total' => $event[dbEvent::field_participants_total],
        'deadline_name' => dbEvent::field_deadline,
        'deadline' => date(event_cfg_date_str, strtotime($event[dbEvent::field_deadline])),
        'title_name' => dbEventItem::field_title,
        'title' => $event[dbEventItem::field_title]
      );
    }

    $data = array(
      'header' => event_header_event_list,
      'intro_class' => ($this->isMessage()) ? 'message' : 'intro',
      'intro' => ($this->isMessage()) ? $this->getMessage() : event_intro_event_list,
      'th' => $th,
      'rows' => $rows,
      'show_all_link' => sprintf('%s&%s=%s&%s=1', $this->page_link, self::request_action, self::action_list, self::request_show_all),
      'show_all' => event_label_show_all
    );
    return $this->getTemplate('backend.event.list.htt', $data);
  } // dlgList()
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
      'text' => event_text_select_no_event
    );
    foreach ($events as $event) {
      $suggest_options[] = array(
        'value' => $event[dbEventItem::field_id],
        'text' => sprintf('[ %s ] %s', date(event_cfg_date_str, strtotime($event[dbEvent::field_event_date_from])), $event[dbEventItem::field_title])
      );
    }

    $data = array(
      'form_name' => 'event_suggest',
      'form_action' => $this->page_link,
      'action_name' => self::request_action,
      'action_value' => self::action_edit,
      'header' => event_header_suggest_event,
      'intro' => event_intro_suggest_event,
      'suggest_request' => self::request_suggestion,
      'suggest_label' => event_label_select_event,
      'suggest_options' => $suggest_options,
      'btn_ok' => event_btn_ok,
      'btn_abort' => event_btn_abort,
      'abort_location' => $this->page_link
    );
    return $this->getTemplate('backend.event.suggest.htt', $data);
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
        $this->setError(sprintf(event_error_id_invalid, $event_id));
        return false;
      }
      $event = $event[0];
      $item_id = $event[dbEvent::field_event_item];
      if ((date('H', strtotime($event[dbEvent::field_event_date_from])) !== 0) && (date('i', strtotime($event[dbEvent::field_event_date_from])) !== 0)) {
        $time_start = date(event_cfg_time_str, strtotime($event[dbEvent::field_event_date_from]));
      }
      else {
        $time_start = '';
      }
      if ((date('H', strtotime($event[dbEvent::field_event_date_to])) !== 0) && (date('i', strtotime($event[dbEvent::field_event_date_to])) !== 0)) {
        $time_end = date(event_cfg_time_str, strtotime($event[dbEvent::field_event_date_to]));
      }
      else {
        $time_end = '';
      }
    }
    elseif (!isset($_REQUEST[self::request_suggestion])) {
      // erster Aufruf - Datenuebernahme von bestehenden Events anbieten
      return $this->dlgSuggestEvent();
    }
    elseif (isset($_REQUEST[self::request_suggestion]) && ($_REQUEST[self::request_suggestion] != -1)) {
      $item_id = -1;
      $event = $dbEvent->getFields();
      $event[dbEvent::field_status] = dbEvent::status_active;
      $time_start = '';
      $time_end = '';
      $where = array(
        dbEventItem::field_id => $_REQUEST[self::request_suggestion]
      );
      $item = array();
      if (!$dbEventItem->sqlSelectRecord($where, $item)) {
        $this->setError($dbEventItem->getError());
        return false;
      }
      $item = $item[0];
      $event = array_merge($event, $item);
      $this->setMessage(sprintf(event_msg_event_take_suggestion, $_REQUEST[self::request_suggestion]));
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
              $time_start = ((date('H', $x) !== 0) || (date('i', $x) !== 0)) ? date(event_cfg_time_str, $x) : '';
            }
            break;
          case dbEvent::field_event_date_to :
            if (false !== ($x = strtotime($_REQUEST[$key]))) {
              $event[$key] = date('Y-m-d H:i:s', $x);
              $time_end = ((date('H', $x) !== 0) || (date('i', $x) !== 0)) ? date(event_cfg_time_str, $x) : '';
            }
            break;
          default :
            $event[$key] = $_REQUEST[$key];
        endswitch
        ;
      }
    }
    if (isset($_REQUEST[self::request_time_start])) $time_start = $_REQUEST[self::request_time_start];
    if (isset($_REQUEST[self::request_time_end])) $time_end = $_REQUEST[self::request_time_end];

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
      'text' => event_text_no_group
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

    // short description
    ob_start();
    show_wysiwyg_editor(dbEventItem::field_desc_short, dbEventItem::field_desc_short, stripslashes($event[dbEventItem::field_desc_short]), '99%', '200px');
    $editor_short = ob_get_contents();
    ob_end_clean();

    // long description
    ob_start();
    show_wysiwyg_editor(dbEventItem::field_desc_long, dbEventItem::field_desc_long, stripslashes($event[dbEventItem::field_desc_long]), '99%', '300px');
    $editor_long = ob_get_contents();
    ob_end_clean();

    $fields = array(
      'date_from' => array(
        'name' => dbEvent::field_event_date_from,
        'id' => 'datepicker_1',
        'label' => event_label_event_date_from,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_event_date_from]))) ? date(event_cfg_date_str, $x) : ''
      ),
      'date_to' => array(
        'name' => dbEvent::field_event_date_to,
        'id' => 'datepicker_2',
        'label' => event_label_event_date_to,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_event_date_to]))) ? date(event_cfg_date_str, $x) : ''
      ),
      'time_start' => array(
        'name' => self::request_time_start,
        'label' => event_label_event_time_start,
        'value' => $time_start
      ),
      'time_end' => array(
        'name' => self::request_time_end,
        'label' => event_label_event_time_end,
        'value' => $time_end
      ),
      'publish_date_from' => array(
        'name' => dbEvent::field_publish_date_from,
        'label' => event_label_publish_from,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_publish_date_from]))) ? date(event_cfg_date_str, $x) : '',
        'id' => 'datepicker_3'
      ),
      'publish_date_to' => array(
        'name' => dbEvent::field_publish_date_to,
        'label' => event_label_publish_to,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_publish_date_to]))) ? date(event_cfg_date_str, $x) : '',
        'id' => 'datepicker_4'
      ),
      'participants_max' => array(
        'name' => dbEvent::field_participants_max,
        'label' => event_label_participants_max,
        'value' => $event[dbEvent::field_participants_max]
      ),
      'participants_total' => array(
        'name' => dbEvent::field_participants_total,
        'label' => event_label_participants_total,
        'value' => $event[dbEvent::field_participants_total]
      ),
      'deadline' => array(
        'name' => dbEvent::field_deadline,
        'label' => event_label_deadline,
        'value' => (false !== ($x = strtotime($event[dbEvent::field_deadline]))) ? date(event_cfg_date_str, $x) : '',
        'id' => 'datepicker_5'
      ),
      'costs' => array(
        'name' => dbEventItem::field_costs,
        'label' => event_label_event_costs,
        'value' => sprintf(event_cfg_currency, number_format((float) $event[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator))
      ),
      'group' => array(
        'name' => dbEvent::field_event_group,
        'label' => event_label_event_group,
        'value' => $group
      ),
      'status' => array(
        'name' => dbEvent::field_status,
        'label' => event_label_status,
        'value' => $status
      ),
      'title' => array(
        'name' => dbEventItem::field_title,
        'label' => event_label_event_title,
        'value' => $event[dbEventItem::field_title]
      ),
      'short_description' => array(
        'name' => dbEventItem::field_desc_short,
        'label' => event_label_short_description,
        'value' => $editor_short
      ),
      'long_description' => array(
        'name' => dbEventItem::field_desc_long,
        'label' => event_label_long_description,
        'value' => $editor_long
      ),
      'location' => array(
        'name' => dbEventItem::field_location,
        'label' => event_label_event_location,
        'value' => $event[dbEventItem::field_location]
      ),
      'link' => array(
        'name' => dbEventItem::field_desc_link,
        'label' => event_label_event_link,
        'value' => $event[dbEventItem::field_desc_link]
      ),
      'perma_link' => array(
        'name' => dbEvent::field_perma_link,
        'label' => event_label_perma_link,
        'value' => $event[dbEvent::field_perma_link],
        'hint' => event_hint_perma_link
      )
    );

    $data = array(
      'form_name' => 'event_edit',
      'form_action' => $this->page_link,
      'action_name' => self::request_action,
      'action_value' => self::action_edit_check,
      'language' => (LANGUAGE == 'EN') ? '' : strtolower(LANGUAGE),
      'event_name' => dbEvent::field_id,
      'event_value' => $event_id,
      'item_name' => dbEventItem::field_id,
      'item_value' => $item_id,
      'suggestion_name' => self::request_suggestion,
      'suggestion_value' => -1,
      'header' => event_header_edit_event,
      'is_intro' => ($this->isMessage()) ? 0 : 1,
      'intro' => ($this->isMessage()) ? $this->getMessage() : event_intro_edit_event,
      'btn_ok' => event_btn_ok,
      'btn_abort' => event_btn_abort,
      'abort_location' => $this->page_link,
      'event' => $fields
    );
    return $this->getTemplate('backend.event.edit.htt', $data);
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
              if (isset($_REQUEST[self::request_time_start]) && !empty($_REQUEST[self::request_time_start])) {
                $time = $_REQUEST[self::request_time_start];
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
                  $message .= sprintf(event_msg_time_invalid, $time, event_label_event_time_start);
                }
                else {
                  // time ok
                  $_REQUEST[$request] = date('Y-m-d H:i:s', mktime($H, $i, 0, date('m', $x), date('d', $x), date('Y', $x)));
                  unset($_REQUEST[self::request_time_start]);
                  $start_date_ok = true;
                }
              }
              else {
                unset($_REQUEST[self::request_time_start]);
                $start_date_ok = true;
              }
            }
            else {
              $checked = false;
              $message .= sprintf(event_msg_date_invalid, $_REQUEST[$request], event_label_event_date_from);
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
              $message .= sprintf(event_msg_date_invalid, $_REQUEST[$request], event_label_event_date_to);
              break;
            }
            // check time
            $_REQUEST[$request] = date('Y-m-d H:i:s', $x);
            $y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
            if (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) < mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y))) {
              $checked = false;
              $message .= event_msg_date_from_to_invalid;
              break;
            }
            if (isset($_REQUEST[self::request_time_end]) && !empty($_REQUEST[self::request_time_end])) {
              $time = $_REQUEST[self::request_time_end];
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
                $message .= sprintf(event_msg_time_invalid, $time, event_label_event_time_end);
              }
              else {
                // time ok
                $_REQUEST[$request] = date('Y-m-d H:i:s', mktime($H, $i, 0, date('m', $x), date('d', $x), date('Y', $x)));
                unset($_REQUEST[self::request_time_end]);
                $end_date_ok = true;
              }
            }
            else {
              // set time to the end of the day
              $_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23, 59, 0, date('m', $x), date('d', $x), date('Y', $x)));
              unset($_REQUEST[self::request_time_end]);
              $end_date_ok = true;
            }
            break;
          case dbEvent::field_publish_date_from :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
              if ($start_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) > mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
                $message .= event_msg_publish_from_invalid;
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
              $message .= event_msg_publish_from_check;
              $checked = false;
            }
            break;
          case dbEvent::field_publish_date_to :
            if (false !== ($x = strtotime($_REQUEST[$request]))) {
              $y = strtotime($_REQUEST[dbEvent::field_event_date_to]);
              if ($end_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) < mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
                $message .= event_msg_publish_to_invalid;
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
              $message .= event_msg_publish_to_check;
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
                $message .= event_msg_deadline_invalid;
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
              $message .= event_msg_event_title_missing;
              $checked = false;
            }
            break;
          case dbEventItem::field_desc_short :
            if (empty($_REQUEST[$request])) {
              $message .= event_msg_short_description_empty;
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
        $message .= sprintf(event_msg_event_inserted, $event_id);
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
        $message .= sprintf(event_msg_event_updated, $event_id);
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
    global $dbEventCfg;
    global $dbEvent;
    global $kitLibrary;

    if (!$dbEventCfg->getValue(dbEventCfg::cfgQRCodeExec)) return true;

    $SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbEvent->getTableName(), dbEvent::field_id, $event_id);
    $event = array();
    if (!$dbEvent->sqlExec($SQL, $event)) {
      $this->setError($dbEvent->getError());
      return false;
    }
    if (count($event) < 1) {
      $this->setError(sprintf(event_error_id_invalid, $event_id));
      return false;
    }
    $event = $event[0];

    $c_type = $dbEventCfg->getValue(dbEventCfg::cfgQRCodeContent);

    if ($c_type == 2) {
      // iCal einlesen
      $dir = $kitLibrary->removeLeadingSlash($dbEventCfg->getValue(dbEventCfg::cfgICalDir));
      $dir = $kitLibrary->addSlash($dir);
      $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
      $filename = $event[dbEvent::field_ical_file];
      if (empty($filename)) {
        // es existiert keine iCal Datei
        $this->setMessage(event_msg_ical_file_undefined);
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
        $this->setMessage(event_msg_perma_link_undefined);
        return true;
      }
      $text = WB_URL . PAGES_DIRECTORY . $event[dbEvent::field_perma_link];
    }

    $level = $dbEventCfg->getValue(dbEventCfg::cfgQRCodeECLevel);
    $size = $dbEventCfg->getValue(dbEventCfg::cfgQRCodeSize);
    $margin = $dbEventCfg->getValue(dbEventCfg::cfgQRCodeMargin);

    $filename = sprintf('%s-%05d.png', date('Ymd-Hi', strtotime($event[dbEvent::field_event_date_from])), $event[dbEvent::field_id]);
    $dir = $kitLibrary->removeLeadingSlash($dbEventCfg->getValue(dbEventCfg::cfgQRCodeDir));
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
    global $dbEventCfg;
    global $kitLibrary;

    if (!$dbEventCfg->getValue(dbEventCfg::cfgICalExec)) {
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
      $this->setError(sprintf(event_error_id_invalid, $event_id));
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
    $dir = $kitLibrary->removeLeadingSlash($dbEventCfg->getValue(dbEventCfg::cfgICalDir));
    $dir = $kitLibrary->addSlash($dir);
    $dir_path = WB_PATH . MEDIA_DIRECTORY . '/' . $dir;
    if (!file_exists($dir_path)) {
      if (!mkdir($dir_path, 0755)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(tool_error_mkdir, $dir_path)));
        return false;
      }
    }
    if (!file_put_contents($dir_path . $filename, $ical)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(event_error_file_create, $dir . $filename)));
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
    global $dbEventCfg;

    if (!$dbEventCfg->getValue(dbEventCfg::cfgPermaLinkExec)) return true;

    $where = array(
      dbEvent::field_id => $event_id
    );
    $event = array();
    if (!$dbEvent->sqlSelectRecord($where, $event)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEvent->getError()));
      return false;
    }
    if (count($event) < 1) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(event_error_id_invalid, $event_id)));
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
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(event_error_id_invalid, $event[dbEvent::field_event_group])));
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
        $this->setMessage(sprintf(event_msg_perma_link_created, $perma_link));
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
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(event_error_id_invalid, $event_group)));
          return false;
        }
        $redirect = $group[0][dbEventGroup::field_redirect_page];
        if (empty($redirect)) {
          $this->setMessage(event_msg_perma_link_redirect_missing);
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
        $this->setMessage(sprintf(event_msg_perma_link_created, $perma_link));
        return true;
      }
    }
    elseif ($event[dbEvent::field_perma_link] != $perma_link) {
      // der permaLink wurde geaendert...
      if (empty($event[dbEvent::field_perma_link])) {
        // der permaLink ist neu
        if ($event[dbEvent::field_event_group] == -1) {
          $this->setMessage(event_msg_perma_link_redirect_missing);
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
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(event_error_id_invalid, $event_group)));
          return false;
        }
        $redirect = $group[0][dbEventGroup::field_redirect_page];
        if (empty($redirect)) {
          $this->setMessage(event_msg_perma_link_redirect_missing);
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
        $this->setMessage(sprintf(event_msg_perma_link_created, $perma_link));
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
        $message = sprintf(event_msg_perma_link_deleted, $event[dbEvent::field_perma_link]);
        if (empty($perma_link)) {
          // permaLink wird nur geloescht, kein neuer angelegt...
          $this->setMessage($message);
          return true;
        }
        // neuen permaLink anlegen
        if ($event[dbEvent::field_event_group] == -1) {
          $message .= event_msg_perma_link_redirect_missing;
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
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(event_error_id_invalid, $event_group)));
          return false;
        }
        $redirect = $group[0][dbEventGroup::field_redirect_page];
        if (empty($redirect)) {
          $message .= event_msg_perma_link_redirect_missing;
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
        $message .= sprintf(event_msg_perma_link_created, $perma_link);
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
        $this->setError(sprintf(event_error_id_invalid, $group_id));
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
      'text' => event_text_create_new_group
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
      'text' => event_text_select_redirect_page
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
        'label' => event_label_group_select,
        'value' => $grps,
        // 'location' => sprintf('%s&%s=%s&%s=', $this->page_link,
        // self::request_action, self::action_group, dbEventGroup::field_id),
        'location' => sprintf('javascript:execOnChange(\'%s\', \'%s\');', sprintf('%s&amp;%s=%s%s&amp;%s=', $this->page_link, self::request_action, self::action_group, (defined('LEPTON_VERSION') && isset($_GET['leptoken'])) ? sprintf('&amp;leptoken=%s', $_GET['leptoken']) : '', dbEventGroup::field_id), dbEventGroup::field_id),
        'hint' => event_hint_group_group
      ),
      'name' => array(
        'name' => dbEventGroup::field_name,
        'label' => event_label_group_name,
        'value' => $active_group[dbEventGroup::field_name],
        'hint' => event_hint_group_name
      ),
      'desc' => array(
        'name' => dbEventGroup::field_desc,
        'label' => event_label_group_description,
        'value' => $active_group[dbEventGroup::field_desc],
        'hint' => event_hint_group_desc
      ),
      'status' => array(
        'name' => dbEventGroup::field_status,
        'label' => event_label_status,
        'value' => $status,
        'hint' => event_hint_group_status
      ),
      'perma_pattern' => array(
        'name' => dbEventGroup::field_perma_link_pattern,
        'label' => event_label_group_perma_pattern,
        'value' => $active_group[dbEventGroup::field_perma_link_pattern],
        'hint' => event_hint_group_perma_pattern
      ),
      'redirect_page' => array(
        'name' => dbEventGroup::field_redirect_page,
        'label' => event_label_group_redirect_page,
        'value' => $active_group[dbEventGroup::field_redirect_page],
        'options' => $page_array,
        'hint' => event_hint_group_redirect_page
      )
    );

    $data = array(
      'form_name' => 'event_group',
      'form_action' => $this->page_link,
      'action_name' => self::request_action,
      'action_value' => self::action_group_check,
      'header' => event_header_edit_group,
      'is_intro' => ($this->isMessage()) ? 0 : 1,
      'intro' => ($this->isMessage()) ? $this->getMessage() : event_intro_edit_group,
      'group' => $group,
      'btn_ok' => event_btn_ok,
      'btn_abort' => event_btn_abort,
      'abort_location' => $this->page_link
    );
    return $this->getTemplate('backend.event.group.htt', $data);
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
      $this->setMessage(event_msg_group_name_empty);
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
      $this->setMessage(sprintf(event_msg_group_updated, $group_id));
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
        $this->setMessage(sprintf(event_msg_group_already_exists, $data[dbEventGroup::field_name]));
      }
      elseif (!$dbEventGroup->sqlInsertRecord($data, $group_id)) {
        $this->setError($dbEventGroup->getError());
        return false;
      }
      else {
        $this->setMessage(sprintf(event_msg_group_created, $group_id));
      }
    }
    return $this->dlgEditGroup();
  } // checkEditGroup()

  public function dlgAbout() {
    $data = array(
      'version' => sprintf('%01.2f', $this->getVersion()),
      'img_url' => $this->img_url . '/kit_event_logo_424x283.jpg',
      'release_notes' => file_get_contents(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/CHANGELOG')
    );
    return $this->getTemplate('backend.about.htt', $data);
  } // dlgAbout()

  public function dlgMessages() {
    global $dbEventOrder;
    global $dbEvent;
    global $dbEventItem;
    global $parser;

    $SQL = sprintf("SELECT * FROM %s,%s,%s WHERE %s.%s=%s.%s AND %s.%s=%s.%s ORDER BY %s DESC LIMIT 100", $dbEvent->getTableName(), $dbEventOrder->getTableName(), $dbEventItem->getTableName(), $dbEvent->getTableName(), dbEvent::field_id, $dbEventOrder->getTableName(), dbEventOrder::field_event_id, $dbEvent->getTableName(), dbEvent::field_event_item, $dbEventItem->getTableName(), dbEventItem::field_id, dbEventOrder::field_order_date);
    $messages = array();
    if (!$dbEventOrder->sqlExec($SQL, $messages)) {
      $this->setError($dbEventOrder->getError());
      return false;
    }

    $row = new Dwoo_Template_File($this->template_path . 'backend.order.list.row.htt');

    $items = '';
    $rows = array();
    foreach ($messages as $message) {
      $dt = strtotime($message[dbEventOrder::field_confirm_order]);
      $declared = (checkdate(date('n', $dt), date('j', $dt), date('Y', $dt))) ? event_text_yes : '';
      $name = sprintf('%s, %s', $message[dbEventOrder::field_last_name], $message[dbEventOrder::field_first_name]);
      if (strlen($name) < 3) $name = '';
      $rows[] = array(
        'order_date_link' => sprintf('%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_messages_detail, dbEventOrder::field_id, $message[dbEventOrder::field_id]),
        'order_date' => date(event_cfg_datetime_str, strtotime($message[dbEventOrder::field_order_date])),
        'email' => $message[dbEventOrder::field_email],
        'name' => $name,
        'event' => $message[dbEventItem::field_title],
        'event_date' => date(event_cfg_date_str, strtotime($message[dbEvent::field_event_date_from])),
        'declared' => $declared,
        'message' => $message[dbEventOrder::field_message]
      );
    }

    $data = array(
      'header' => event_header_messages_list,
      'intro' => ($this->isMessage()) ? $this->getMessage() : '',
      'is_intro' => ($this->isMessage()) ? 0 : 1,
      'rows' => $rows,
      'order_date_name' => dbEventOrder::field_order_date,
      'order_date_th' => event_th_date_time,
      'email_name' => dbEventOrder::field_email,
      'email_th' => event_th_email,
      'name_name' => dbEventOrder::field_last_name,
      'name_th' => event_th_name,
      'event_name' => dbEventOrder::field_event_id,
      'event_th' => event_th_event,
      'event_date_name' => dbEvent::field_event_date_from,
      'event_date_th' => event_th_date,
      'declared_name' => dbEventOrder::field_confirm_order,
      'declared_th' => event_th_declared,
      'message_name' => dbEventOrder::field_message,
      'message_th' => event_th_message
    );
    return $this->getTemplate('backend.order.list.htt', $data);
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
      $this->setError(sprintf(event_error_id_invalid, $order_id));
      return false;
    }
    $detail = $detail[0];

    $dt = strtotime($detail[dbEventOrder::field_confirm_order]);
    $declared = (checkdate(date('n', $dt), date('j', $dt), date('Y', $dt))) ? date(event_cfg_datetime_str, $dt) : event_text_no;

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
      'label_email' => event_label_email,
      'label_phone' => event_label_phone,
      'phone' => $detail[dbEventOrder::field_phone],
      'best_time' => $detail[dbEventOrder::field_best_time],
      'label_event' => event_label_event,
      'event' => $detail[dbEventItem::field_title],
      'label_declared' => event_label_declared,
      'label_event_date' => event_label_date,
      'event_date' => date(event_cfg_date_str, strtotime($detail[dbEvent::field_event_date_from])),
      'declared' => $declared,
      'label_message' => event_label_message,
      'message' => $detail[dbEventOrder::field_message],
      'label_free_1' => (!empty($label_free_1)) ? $label_free_1 : sprintf(event_label_free_field_nr, 1),
      'label_free_2' => (!empty($label_free_2)) ? $label_free_2 : sprintf(event_label_free_field_nr, 2),
      'label_free_3' => (!empty($label_free_3)) ? $label_free_3 : sprintf(event_label_free_field_nr, 3),
      'label_free_4' => (!empty($label_free_4)) ? $label_free_4 : sprintf(event_label_free_field_nr, 4),
      'label_free_5' => (!empty($label_free_5)) ? $label_free_5 : sprintf(event_label_free_field_nr, 5),
      'free_1' => substr($detail[dbEventOrder::field_free_1], strpos($detail[dbEventOrder::field_free_1], '|') + 1),
      'free_2' => substr($detail[dbEventOrder::field_free_2], strpos($detail[dbEventOrder::field_free_2], '|') + 1),
      'free_3' => substr($detail[dbEventOrder::field_free_3], strpos($detail[dbEventOrder::field_free_3], '|') + 1),
      'free_4' => substr($detail[dbEventOrder::field_free_4], strpos($detail[dbEventOrder::field_free_4], '|') + 1),
      'free_5' => substr($detail[dbEventOrder::field_free_5], strpos($detail[dbEventOrder::field_free_5], '|') + 1),

      'back_link' => sprintf('%s&%s=%s', $this->page_link, self::request_action, self::action_messages),
      'back_text' => event_text_back
    );
    return $this->getTemplate('backend.order.detail.htt', $data);
  } // dlgMessageDetail()

  /**
   * Dialog zur Konfiguration und Anpassung von kitMarketPlace
   *
   * @return STR dialog
   */
  public function dlgConfig() {
    global $dbEventCfg;
    $SQL = sprintf("SELECT * FROM %s WHERE NOT %s='%s' ORDER BY %s", $dbEventCfg->getTableName(), dbEventCfg::field_status, dbEventCfg::status_deleted, dbEventCfg::field_name);
    $config = array();
    if (!$dbEventCfg->sqlExec($SQL, $config)) {
      $this->setError($dbEventCfg->getError());
      return false;
    }
    $count = array();
    $header = array(
      'identifier' => tool_header_cfg_identifier,
      'value' => tool_header_cfg_value,
      'description' => tool_header_cfg_description
    );

    $items = array();
    // bestehende Eintraege auflisten
    foreach ($config as $entry) {
      $id = $entry[dbEventCfg::field_id];
      $count[] = $id;
      $value = (isset($_REQUEST[dbEventCfg::field_value . '_' . $id])) ? $_REQUEST[dbEventCfg::field_value . '_' . $id] : $entry[dbEventCfg::field_value];
      $value = str_replace('"', '&quot;', stripslashes($value));
      $items[] = array(
        'id' => $id,
        'identifier' => constant($entry[dbEventCfg::field_label]),
        'value' => $value,
        'name' => sprintf('%s_%s', dbEventCfg::field_value, $id),
        'description' => constant($entry[dbEventCfg::field_description])
      );
    }
    $data = array(
      'form_name' => 'event_cfg',
      'form_action' => $this->page_link,
      'action_name' => self::request_action,
      'action_value' => self::action_config_check,
      'items_name' => self::request_items,
      'items_value' => implode(",", $count),
      'head' => tool_header_cfg,
      'intro' => $this->isMessage() ? $this->getMessage() : sprintf(tool_intro_cfg, 'kitEvent'),
      'is_message' => $this->isMessage() ? 1 : 0,
      'items' => $items,
      'btn_ok' => tool_btn_ok,
      'btn_abort' => tool_btn_abort,
      'abort_location' => $this->page_link,
      'header' => $header
    );
    return $this->getTemplate('backend.config.htt', $data);
  } // dlgConfig()

  /**
   * Ueberprueft Aenderungen die im Dialog dlgConfig() vorgenommen wurden
   * und aktualisiert die entsprechenden Datensaetze.
   *
   * @return STR DIALOG dlgConfig()
   */
  public function checkConfig() {
    global $dbEventCfg;
    $message = '';
    // ueberpruefen, ob ein Eintrag geaendert wurde
    if ((isset($_REQUEST[self::request_items])) && (!empty($_REQUEST[self::request_items]))) {
      $ids = explode(",", $_REQUEST[self::request_items]);
      foreach ($ids as $id) {
        if (isset($_REQUEST[dbEventCfg::field_value . '_' . $id])) {
          $value = $_REQUEST[dbEventCfg::field_value . '_' . $id];
          $where = array();
          $where[dbEventCfg::field_id] = $id;
          $config = array();
          if (!$dbEventCfg->sqlSelectRecord($where, $config)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbEventCfg->getError()));
            return false;
          }
          if (sizeof($config) < 1) {
            $this->setError(sprintf(tool_error_cfg_id, $id));
            return false;
          }
          $config = $config[0];
          if ($config[dbEventCfg::field_value] != $value) {
            // Wert wurde geaendert
            if (!$dbEventCfg->setValue($value, $id) && $dbEventCfg->isError()) {
              $this->setError($dbEventCfg->getError());
              return false;
            }
            elseif ($dbEventCfg->isMessage()) {
              $message .= $dbEventCfg->getMessage();
            }
            else {
              // Datensatz wurde aktualisiert
              $message .= sprintf(tool_msg_cfg_id_updated, $config[dbEventCfg::field_name]);
            }
          }
        }
      }
    }
    $this->setMessage($message);
    return $this->dlgConfig();
  } // checkConfig()
} // class eventBackend

?>