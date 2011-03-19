<?php

/**
 * kitEvent
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) die('invalid call of '.$_SERVER['SCRIPT_NAME']);

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.editor.php');

class eventBackend {

	const request_action					= 'act';
	const request_time_start			= 'ets';
	const request_time_end				= 'ete';
	const request_suggestion			= 'sgg';
	
	const action_about						= 'abt';
	const action_default					= 'def';
	const action_edit							= 'edt';
	const action_edit_check				= 'edtc';
	const action_group						= 'grp';
	const action_group_check			= 'grpc';
	const action_list							= 'lst';
	
	private $tab_navigation_array = array(
		self::action_list								=> event_tab_list,
		self::action_edit								=> event_tab_edit,
		self::action_group							=> event_tab_group,
		self::action_about							=> event_tab_about
	);
	
	private $page_link 					= '';
	private $img_url						= '';
	private $template_path			= '';
	private $error							= '';
	private $message						= '';
	
	public function __construct() {
		$this->page_link = ADMIN_URL.'/admintools/tool.php?tool=kit_event';
		$this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/htt/' ;
		$this->img_url = WB_URL. '/modules/'.basename(dirname(__FILE__)).'/images/';
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

  /** Set $this->message to $message
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
  
  /**
   * Return Version of Module
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.php');
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
   * @param REFERENCE $_REQUEST Array
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
  	$html_allowed = array(dbEventItem::field_desc_long, dbEventItem::field_desc_short);
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) { 
   			$_REQUEST[$key] = $this->xssPrevent($value);
  		} 
  	}
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
  	switch ($action):
  	case self::action_about:
  		$this->show(self::action_about, $this->dlgAbout());
  		break;
  	case self::action_edit:
  		$this->show(self::action_edit, $this->dlgEditEvent());
  		break;
  	case self::action_edit_check:
  		$this->show(self::action_edit, $this->checkEditEvent());
  		break;
  	case self::action_group:
  		$this->show(self::action_group, $this->dlgEditGroup());
  		break;
  	case self::action_group_check:
  		$this->show(self::action_group, $this->checkEditGroup());
  		break;
  	case self::action_list:
  	default:
  		$this->show(self::action_list, $this->dlgList());
  		break;
  	endswitch;
  } // action
	
  	
  /**
   * Erstellt eine Navigationsleiste
   * 
   * @param $action - aktives Navigationselement
   * @return STR Navigationsleiste
   */
  public function getNavigation($action) {
  	$result = '';
  	foreach ($this->tab_navigation_array as $key => $value) {
   		($key == $action) ? $selected = ' class="selected"' : $selected = ''; 
	 		$result .= sprintf(	'<li%s><a href="%s">%s</a></li>', 
	 												$selected,
	 												sprintf('%s&%s=%s', $this->page_link, self::request_action, $key),
	 												$value
	 												);
  	}
  	$result = sprintf('<ul class="nav_tab">%s</ul>', $result);
  	return $result;
  } // getNavigation()
  
  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   * 
   * @param $action - aktives Navigationselement
   * @param $content - Inhalt
   * 
   * @return ECHO RESULT
   */
  public function show($action, $content) {
  	global $parser;
  	if ($this->isError()) {
  		$content = $this->getError();
  		$class = ' class="error"';
  	}
  	else {
  		$class = '';
  	}
  	$data = array(
  		'WB_URL'					=> WB_URL,
  		'navigation'			=> $this->getNavigation($action),
  		'class'						=> $class,
  		'content'					=> $content
  	);
  	$parser->output($this->template_path.'backend.body.htt', $data);
  } // show()
  
  public function dlgList() {
  	global $dbEvent;
  	global $dbEventItem;
  	global $dbEventGroup;
  	global $parser;
  	
  	$start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-2, date('Y')));
  	$SQL = sprintf(	"SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s!='%s' AND %s>='%s' ORDER BY %s ASC",
  									$dbEvent->getTableName(),
  									$dbEventItem->getTableName(),
  									$dbEvent->getTableName(),
  									dbEvent::field_event_item,
  									$dbEventItem->getTableName(),
  									dbEventItem::field_id,
  									dbEvent::field_status,
  									dbEvent::status_deleted,
  									dbEvent::field_event_date_from,
  									$start_date,
  									dbEvent::field_event_date_from);
  	$events = array();
  	if (!$dbEvent->sqlExec($SQL, $events)) {
  		$this->setError($dbEvent->getError());
  		return false;
  	}
  	
  	$data = array(
  		'id_name'						=> dbEvent::field_id,
  		'id'								=> event_th_id,
  		'date_from_name'		=> dbEvent::field_event_date_from,
  		'date_from'					=> event_th_date_from,
  		'date_to_name'			=> dbEvent::field_event_date_to,
  		'date_to'						=> event_th_date_to,
  		'group_name'				=> dbEvent::field_event_group,
  		'group'							=> event_th_group,
  		'part_max_name'			=> dbEvent::field_participants_max,
  		'part_max'					=> event_th_participants_max,
  		'part_total_name'		=> dbEvent::field_participants_total,
  		'part_total'				=> event_th_participants_total,
  		'deadline_name'			=> dbEvent::field_deadline,
  		'deadline'					=> event_th_deadline,
  		'title_name'				=> dbEventItem::field_title,
  		'title'							=> event_th_title
  	);
  	
  	$items = $parser->get($this->template_path.'backend.event.list.th.htt', $data);
  	$row = new Dwoo_Template_File($this->template_path.'backend.event.list.row.htt');
  	
  	$flipflop = true;
  	foreach ($events as $event) {
  		($flipflop) ? $flipflop = false : $flipflop = true;
			($flipflop) ? $class = 'flip' : $class = 'flop';
			$where = array(dbEventGroup::field_id => $event[dbEvent::field_event_group]);
			$group = array();
			if (!$dbEventGroup->sqlSelectRecord($where, $group)) {
				$this->setError($dbEventGroup->getError());
				return false;
			}
			$grp = (count($group) > 0) ? $group[0][dbEventGroup::field_name] : '';
			
			$group = -1;
  		$data = array(
  			'flipflop'					=> $class,
	  		'id_name'						=> dbEvent::field_id,
	  		'id'								=> sprintf(	'<a href="%s&%s=%s&%s=%s">%05d</a>',
  																			$this->page_link,
  																			self::request_action,
  																			self::action_edit,
  																			dbEvent::field_id,
  																			$event[dbEvent::field_id],  
  																			$event[dbEvent::field_id]),
	  		'date_from_name'		=> dbEvent::field_event_date_from,
	  		'date_from'					=> date(event_cfg_datetime_str, strtotime($event[dbEvent::field_event_date_from])),
	  		'date_to_name'			=> dbEvent::field_event_date_to,
	  		'date_to'						=> date(event_cfg_datetime_str, strtotime($event[dbEvent::field_event_date_to])),
	  		'group_name'				=> dbEvent::field_event_group,
	  		'group'							=> $grp,
	  		'part_max_name'			=> dbEvent::field_participants_max,
	  		'part_max'					=> $event[dbEvent::field_participants_max],
	  		'part_total_name'		=> dbEvent::field_participants_total,
	  		'part_total'				=> $event[dbEvent::field_participants_total],
	  		'deadline_name'			=> dbEvent::field_deadline,
	  		'deadline'					=> date(event_cfg_date_str, strtotime($event[dbEvent::field_deadline])),
	  		'title_name'				=> dbEventItem::field_title,
	  		'title'							=> $event[dbEventItem::field_title]
	  	);
	  	$items .= $parser->get($row, $data);
  	}
  	
  	$data = array(
  		'header'		=> event_header_event_list,
  		'intro'			=> sprintf('<div class="intro">%s</div>', event_intro_event_list),
  		'items'			=> $items
  	);
  	return $parser->get($this->template_path.'backend.event.list.htt', $data);
  } // dlgList()
  
  public function dlgSuggestEvent() {
  	global $dbEvent;
  	global $dbEventItem;
  	global $parser;
  	
  	$SQL = sprintf(	"SELECT %s.%s,%s,%s FROM %s, %s WHERE %s.%s = %s.%s AND %s!='%s' ORDER BY %s DESC",
  									$dbEventItem->getTableName(),
  									dbEventItem::field_id,
  									dbEvent::field_event_date_from,
  									dbEventItem::field_title,
  									$dbEvent->getTableName(),
  									$dbEventItem->getTableName(),
  									$dbEvent->getTableName(),
  									dbEvent::field_event_item,
  									$dbEventItem->getTableName(),
  									dbEventItem::field_id,
  									dbEvent::field_status,
  									dbEvent::status_deleted,
  									dbEvent::field_event_date_from);
  	$events = array();
  	if (!$dbEvent->sqlExec($SQL, $events)) {
  		$this->setError($dbEvent->getError());
  		return false;
  	}
  	
  	$option = sprintf('<option value="-1">%s</option>', event_text_select_no_event);
  	foreach ($events as $event) {
  		$option .= sprintf(	'<option value="%d">[ %s ] %s</option>', 
  												$event[dbEventItem::field_id], 
  												date(event_cfg_date_str, strtotime($event[dbEvent::field_event_date_from])), 
  												$event[dbEventItem::field_title]);
  	}
  	
  	$data = array(
  		'form_name'				=> 'event_suggest',
  		'form_action'			=> $this->page_link,
  		'action_name'			=> self::request_action,
  		'action_value'		=> self::action_edit,
  		'header'					=> event_header_suggest_event,
  		'intro'						=> sprintf('<div class="intro">%s</div>', event_intro_suggest_event),
  		'css'							=> self::request_suggestion,
  		'label'						=> event_label_select_event,
  		'value'						=> sprintf('<select name="%s">%s</select>', self::request_suggestion, $option),
  		'btn_ok'					=> event_btn_ok,
  		'btn_abort'				=> event_btn_abort,
  		'abort_location'	=> $this->page_link
  	);	
  	return $parser->get($this->template_path.'backend.event.suggest.htt', $data); 
  } // dlgEventSuggestion()
  
  public function dlgEditEvent() {
  	global $dbEvent;
  	global $dbEventGroup;
  	global $dbEventItem;
  	global $parser;
  	
  	$event_id = (isset($_REQUEST[dbEvent::field_id]) && ($_REQUEST[dbEvent::field_id] > 0)) ? $_REQUEST[dbEvent::field_id] : -1;
  	if ($event_id !== -1) {
  		$SQL = sprintf(	"SELECT * FROM %s, %s WHERE %s.%s = %s.%s AND %s.%s='%s' AND %s.%s!='%s'",
  										$dbEvent->getTableName(),
  										$dbEventItem->getTableName(),
  										$dbEvent->getTableName(),
  										dbEvent::field_event_item,
  										$dbEventItem->getTableName(),
  										dbEventItem::field_id,
  										$dbEvent->getTableName(),
  										dbEvent::field_id,
  										$event_id,
  										$dbEvent->getTableName(),
  										dbEvent::field_status,
  										dbEvent::status_deleted);
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
  		$where = array(dbEventItem::field_id => $_REQUEST[self::request_suggestion]);
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
  			switch ($key):
  			case dbEvent::field_event_date_from:
  				if (false !== ($x = strtotime($_REQUEST[$key]))) {
  					$event[$key] = date('Y-m-d H:i:s', $x);
  					$time_start = ((date('H', $x) !== 0) || (date('i', $x) !== 0)) ? date(event_cfg_time_str, $x) : '';	
  				}
  				break;
  			case dbEvent::field_event_date_to:
  				if (false !== ($x = strtotime($_REQUEST[$key]))) {
  					$event[$key] = date('Y-m-d H:i:s', $x);
  					$time_end = ((date('H', $x) !== 0) || (date('i', $x) !== 0)) ? date(event_cfg_time_str, $x) : '';	
  				}
  				break;
  			default:
  				$event[$key] = $_REQUEST[$key];
  			endswitch;
  		}
  	}
  	if (isset($_REQUEST[self::request_time_start])) $time_start = $_REQUEST[self::request_time_start];
		if (isset($_REQUEST[self::request_time_end])) $time_end = $_REQUEST[self::request_time_end];
  	
  	$data = array();
  	
  	// set event date from
  	$date = (false !== ($x = strtotime($event[dbEvent::field_event_date_from]))) ? date(event_cfg_date_str, $x) : '';
  	$data[] = array(
  		'css'			=> dbEvent::field_event_date_from,
  		'label'		=> event_label_event_date_from,
  		'value'		=> sprintf(	'<input type="text" name="%s" value="%s" id="%s" />',
  													dbEvent::field_event_date_from,
  													$date,
  													'datepicker_1')
  	);
  	
  	// set event date to
  	$date = (false !== ($x = strtotime($event[dbEvent::field_event_date_to]))) ? date(event_cfg_date_str, $x) : '';
  	$data[] = array(
  		'css'			=> dbEvent::field_event_date_to,
  		'label'		=> event_label_event_date_to,
  		'value'		=> sprintf(	'<input type="text" name="%s" value="%s" id="%s" />',
  													dbEvent::field_event_date_to,
  													$date,
  													'datepicker_2')
  	);
  	
  	// set event time start
  	$data[] = array(
  		'css'			=> self::request_time_start,
  		'label'		=> event_label_event_time_start,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													self::request_time_start,
  													$time_start)	
  	);
  	
  	// set event time end
  	$data[] = array(
  		'css'			=> self::request_time_end,
  		'label'		=> event_label_event_time_end,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													self::request_time_end,
  													$time_end)	
  	);
  	
  	// set publish date from
  	$date = (false !== ($x = strtotime($event[dbEvent::field_publish_date_from]))) ? date(event_cfg_date_str, $x) : '';
  	$data[] = array(
  		'css'			=> dbEvent::field_publish_date_from,
  		'label'		=> event_label_publish_from,
  		'value'		=> sprintf(	'<input type="text" name="%s" value="%s" id="%s" />',
  													dbEvent::field_publish_date_from,
  													$date,
  													'datepicker_3')
  	);
  	
  	// set publish date to
  	$date = (false !== ($x = strtotime($event[dbEvent::field_publish_date_to]))) ? date(event_cfg_date_str, $x) : '';
  	$data[] = array(
  		'css'			=> dbEvent::field_publish_date_to,
  		'label'		=> event_label_publish_to,
  		'value'		=> sprintf(	'<input type="text" name="%s" value="%s" id="%s" />',
  													dbEvent::field_publish_date_to,
  													$date,
  													'datepicker_4')
  	);
  	
  	// participants max
  	$data[] = array(
  		'css'			=> dbEvent::field_participants_max,
  		'label'		=> event_label_participants_max,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													dbEvent::field_participants_max,
  													$event[dbEvent::field_participants_max])
  	);
  	
  	// participants total
  	$data[] = array(
  		'css'			=> dbEvent::field_participants_total,
  		'label'		=> event_label_participants_total,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													dbEvent::field_participants_total,
  													$event[dbEvent::field_participants_total])
  	);
  	
  	// set deadline
  	$date = (false !== ($x = strtotime($event[dbEvent::field_deadline]))) ? date(event_cfg_date_str, $x) : '';
  	$data[] = array(
  		'css'			=> dbEvent::field_deadline,
  		'label'		=> event_label_deadline,
  		'value'		=> sprintf(	'<input type="text" name="%s" value="%s" id="%s" />',
  													dbEvent::field_deadline,
  													$date,
  													'datepicker_5')
  	);
  	
  	// costs
  	$data[] = array(
  		'css'			=> dbEventItem::field_costs,
  		'label'		=> event_label_event_costs,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													dbEventItem::field_costs,
  													sprintf(event_cfg_currency, number_format($event[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator)))
  	);
  	
  	// group
  	$SQL = sprintf( "SELECT * FROM %s WHERE %s='%s'", 
  									$dbEventGroup->getTableName(),
  									dbEventGroup::field_status,
  									dbEventGroup::status_active);
  	$groups = array();
  	if (!$dbEventGroup->sqlExec($SQL, $groups)) {
  		$this->setError($dbEventGroup->getError());
  		return false;
  	}
  	$option = sprintf('<option value="-1"%s>%s</option>', ($event[dbEvent::field_event_group] == -1) ? ' selected="selected"' : '', event_text_no_group);
  	foreach ($groups as $group) {
  		$selected = ($group[dbEventGroup::field_id] == $event[dbEvent::field_event_group]) ? ' selected="selected"' : '';
  		$option .= sprintf('<option value="%d"%s>%s</option>', $group[dbEventGroup::field_id], $selected, $group[dbEventGroup::field_name]);
  	}
  	$data[] = array(
  		'css'			=> dbEvent::field_event_group,
  		'label'		=> event_label_event_group,
  		'value'		=> sprintf( '<select name="%s">%s</select>',
  													dbEvent::field_event_group,
  													$option)
  	);
  	
  	// status
  	$option = '';
  	foreach ($dbEvent->status_array as $value => $status) {
  		$selected = ($event[dbEvent::field_status] == $value) ? ' selected="selected"' : '';
  		$option .= sprintf('<option value="%s"%s>%s</option>', $value, $selected, $status);
  	}
  	$data[] = array(
  		'css'			=> dbEvent::field_status,
  		'label'		=> event_label_status,
  		'value'		=> sprintf( '<select name="%s">%s</select>',
  													dbEvent::field_status,
  													$option)
  	);
  	
  	
  	// title
  	$data[] = array(
  		'css'			=> dbEventItem::field_title,
  		'label'		=> event_label_event_title,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													dbEventItem::field_title,
  													$event[dbEventItem::field_title])
  	);
  	
  	// short description
  	ob_start();
			show_wysiwyg_editor(dbEventItem::field_desc_short, dbEventItem::field_desc_short, $event[dbEventItem::field_desc_short], '99%', '200px');
			$editor = ob_get_contents();
		ob_end_clean();
		$data[] = array(
			'css'			=> dbEventItem::field_desc_short,
			'label'		=> event_label_short_description,
			'value'		=> $editor
		);
		
  	// long description
  	ob_start();
			show_wysiwyg_editor(dbEventItem::field_desc_long, dbEventItem::field_desc_long, $event[dbEventItem::field_desc_long], '99%', '300px');
			$editor = ob_get_contents();
		ob_end_clean();
		$data[] = array(
			'css'			=> dbEventItem::field_desc_long,
			'label'		=> event_label_long_description,
			'value'		=> $editor
		);
		
		// location
  	$data[] = array(
  		'css'			=> dbEventItem::field_location,
  		'label'		=> event_label_event_location,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													dbEventItem::field_location,
  													$event[dbEventItem::field_location])
  	);
  	
  	// link
  	$data[] = array(
  		'css'			=> dbEventItem::field_desc_link,
  		'label'		=> event_label_event_link,
  		'value'		=> sprintf( '<input type="text" name="%s" value="%s" />',
  													dbEventItem::field_desc_link,
  													$event[dbEventItem::field_desc_link])
  	);
  	
  	
  	
  	$items = '';
  	$row = new Dwoo_Template_File($this->template_path.'backend.event.edit.row.htt');
  	foreach ($data as $item) {
  		$items .= $parser->get($row, $item);	
  	}
  	
  	$intro = ($this->isMessage()) ? sprintf('<div class="message">%s</div>', $this->getMessage()) : sprintf('<div class="intro">%s</div>', event_intro_edit_event);
  	
  	$data = array(
  		'form_name'				=> 'event_edit',
  		'form_action'			=> $this->page_link,
  		'action_name'			=> self::request_action,
  		'action_value'		=> self::action_edit_check,
  		'language'				=> (LANGUAGE == 'EN') ? '' : strtolower(LANGUAGE),
  		'event_name'			=> dbEvent::field_id,
  		'event_value'			=> $event_id,
  		'item_name'				=> dbEventItem::field_id,
  		'item_value'			=> $item_id,
  		'suggestion_name'	=> self::request_suggestion,
  		'suggestion_value'=> -1,
  		'header'					=> event_header_edit_event,
  		'intro'						=> $intro, 
  		'items'						=> $items,
  		'btn_ok'					=> event_btn_ok,
  		'btn_abort'				=> event_btn_abort,
  		'abort_location'	=> $this->page_link
  	);
  	return $parser->get($this->template_path.'backend.event.edit.htt', $data);
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
  			switch ($request):
  				case dbEvent::field_event_date_from: 
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
  				case dbEvent::field_event_date_to: 
  					// check event date TO
  					$x = strtotime($_REQUEST[$request]);
  					if (!$x  && $start_date_ok) {
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
  				case dbEvent::field_publish_date_from:
  					if (false !== ($x = strtotime($_REQUEST[$request]))) {
  						$y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
  						if ($start_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) > mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
  							$message .= event_msg_publish_from_invalid;
  							$checked = false;
  							break;
  						}
  						$_REQUEST[$request] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $x), date('d', $x)-14, date('Y', $x)));
  					}
  					elseif ($start_date_ok) {
  						$y = strtotime($_REQUEST[dbEvent::field_event_date_from]);
  						$_REQUEST[$request] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $y), date('d', $y)-14, date('Y', $y)));
  					}
  					else {
  						$message .= event_msg_publish_from_check;
  						$checked = false;
  					}
  					break;
  				case dbEvent::field_publish_date_to:
  					if (false !== ($x = strtotime($_REQUEST[$request]))) {
  						$y = strtotime($_REQUEST[dbEvent::field_event_date_to]);
  						if ($end_date_ok && (mktime(0, 0, 0, date('m', $x), date('d', $x), date('Y', $x)) < mktime(0, 0, 0, date('m', $y), date('d', $y), date('Y', $y)))) {
  							$message .= event_msg_publish_to_invalid;
  							$checked = false;
  							break;
  						}
  						$_REQUEST[$request] = date('Y-m-d H:i:s', mktime(23,59,59,date('n', $x), date('j', $x), date('Y', $x)));
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
  				case dbEvent::field_participants_max:
  					$x = (int) $_REQUEST[$request];
  					if ($x < 1) $x = -1;
  					$_REQUEST[$request] = $x;
  					break;
  				case dbEvent::field_participants_total:
  					$x = (int) $_REQUEST[$request];
  					if ($x < 1) $x = 0;
  					$_REQUEST[$request] = $x;
  					break;
  				case dbEventItem::field_costs:
  					$x = (float) $_REQUEST[$request];
  					if ($x < 1) $x = -1;
  					$_REQUEST[$request] = $x;
  					break;
  				case dbEvent::field_deadline:
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
  				case dbEventItem::field_title:
  					if (empty($_REQUEST[$request])) {
  						$message .= event_msg_event_title_missing;
  						$checked = false;
  					}
  					break;
  				case dbEventItem::field_desc_short:
  					if (empty($_REQUEST[$request])) {
  						$message .= event_msg_short_description_empty;
  						$checked = false;
  					}
  					break;
  			endswitch;
  		}
  	}
  	
  	if ($checked) {
  		// Datensatz übernehmen
  		$event = array(
  			dbEvent::field_deadline 					=> $_REQUEST[dbEvent::field_deadline],
  			dbEvent::field_event_date_from		=> $_REQUEST[dbEvent::field_event_date_from],
  			dbEvent::field_event_date_to			=> $_REQUEST[dbEvent::field_event_date_to],
  			dbEvent::field_event_group				=> $_REQUEST[dbEvent::field_event_group],
  			dbEvent::field_participants_max		=> $_REQUEST[dbEvent::field_participants_max],
  			dbEvent::field_participants_total	=> $_REQUEST[dbEvent::field_participants_total],
  			dbEvent::field_publish_date_from	=> $_REQUEST[dbEvent::field_publish_date_from],
  			dbEvent::field_publish_date_to		=> $_REQUEST[dbEvent::field_publish_date_to],
  			dbEvent::field_status							=> $_REQUEST[dbEvent::field_status]
  		);
  		$item = array(
  			dbEventItem::field_costs					=> $_REQUEST[dbEventItem::field_costs],
  			dbEventItem::field_desc_link			=> $_REQUEST[dbEventItem::field_desc_link],
  			dbEventItem::field_desc_long			=> $_REQUEST[dbEventItem::field_desc_long],
  			dbEventItem::field_desc_short			=> $_REQUEST[dbEventItem::field_desc_short],
  			dbEventItem::field_location				=> $_REQUEST[dbEventItem::field_location],
  			dbEventItem::field_title					=> $_REQUEST[dbEventItem::field_title]
  		);
  		if ($event_id == -1) {
  			// neuer Datensatz
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
  			$where = array(dbEventItem::field_id => $item_id);
  			if (!$dbEventItem->sqlUpdateRecord($item, $where)) {
  				$this->setError($dbEventItem->getError());
  				return false;
  			}
  			$where = array(dbEvent::field_id => $event_id);
  			if (!$dbEvent->sqlUpdateRecord($event, $where)) {
  				$this->setError($dbEvent->getError());
  				return false;
  			}
  			$message .= sprintf(event_msg_event_updated, $event_id);
  		}
  		foreach ($event as $key => $value) {
  			unset($_REQUEST[$key]);
  		}
  		foreach ($item as $key => $value) {
  			unset($_REQUEST[$key]);
  		}
  		$_REQUEST[dbEvent::field_id] = $event_id;
  	}
  	
  	$this->setMessage($message);
  	return $this->dlgEditEvent();
  } // checkEditEvent()
  
  /**
   * Create or edit event groups
   * @return STR dialog
   */
  public function dlgEditGroup() {
  	global $dbEventGroup;
  	global $parser;
  	
  	$group_id = (isset($_REQUEST[dbEventGroup::field_id]) && ($_REQUEST[dbEventGroup::field_id] > 0)) ? $_REQUEST[dbEventGroup::field_id] : -1;
  	
  	// get active event group
  	if ($group_id > 0) {
  		$SQL = sprintf( "SELECT * FROM %s WHERE %s='%s' AND %s!='%s'",
  										$dbEventGroup->getTableName(),
  										dbEventGroup::field_id,
  										$group_id,
  										dbEventGroup::field_status,
  										dbEventGroup::status_deleted);
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
  	$SQL = sprintf( "SELECT %s, %s FROM %s WHERE %s!='%s' ORDER BY %s",
  									dbEventGroup::field_id,
  									dbEventGroup::field_name,
  									$dbEventGroup->getTableName(),
  									dbEventGroup::field_status,
  									dbEventGroup::status_deleted,
  									dbEventGroup::field_name);
  	$all_groups = array();
  	if (!$dbEventGroup->sqlExec($SQL, $all_groups)) {
  		$this->setError($dbEventGroup->getError());
  		return false;
  	}
  	
  	$data = array();
  	// select event group
  	$option = sprintf('<option value="-1">%s</option>', event_text_create_new_group);
  	foreach ($all_groups as $group) {
  		$selected = ($group[dbEventGroup::field_id] == $group_id) ? ' selected="selected"' : '';
  		$option .= sprintf('<option value="%s"%s>%s</option>', $group[dbEventGroup::field_id], $selected, $group[dbEventGroup::field_name]);
  	}
  	$data[] = array(
  		'css'			=> dbEventGroup::field_id,
  		'label'		=> event_label_group_select,
  		'value'		=> sprintf(	'<select name="%s" onchange="javascript: window.location=\'%s\'+this.value; return false;">%s</select>', 
  													dbEventGroup::field_id,
  													sprintf('%s&%s=%s&%s=',
  																	$this->page_link,
  																	self::request_action,
  																	self::action_group,
  																	dbEventGroup::field_id
  																	), 
  													$option)
  	);
  	
  	// name of the group
  	$data[] = array(
  		'css'			=> dbEventGroup::field_name,
  		'label'		=> event_label_group_name,
  		'value'		=> sprintf(	'<input type="text" name="%s" value="%s" />',	dbEventGroup::field_name,	$active_group[dbEventGroup::field_name])
  	);
  	
  	// description of the group
  	$data[] = array(
  		'css'			=> dbEventGroup::field_desc,
  		'label'		=> event_label_group_description,
  		'value'		=> sprintf('<textarea name="%s">%s</textarea>', dbEventGroup::field_desc, $active_group[dbEventGroup::field_desc])
  	);
  	
  	$option = '';
  	$status_array = $dbEventGroup->status_array;
  	if ($group_id == -1) unset($status_array[dbEventGroup::status_deleted]);
  	foreach ($status_array as $value => $name) {
  		$selected = ($value == $active_group[dbEventGroup::field_status]) ? ' selected="selected"' : '';
  		$option .= sprintf('<option value="%s"%s>%s</option>', $value, $selected, $name);
  	}
  	$data[] = array(
  		'css'			=> dbEventGroup::field_status,
  		'label'		=> event_label_status,
  		'value'		=> sprintf('<select name="%s">%s</select>', dbEventGroup::field_status, $option)
  	);
  	
  	$items = '';
  	$row = new Dwoo_Template_File($this->template_path.'backend.event.group.row.htt');
  	foreach ($data as $item) {
  		$items .= $parser->get($row, $item);	
  	}
  	
  	$intro = ($this->isMessage()) ? sprintf('<div class="message">%s</div>', $this->getMessage()) : sprintf('<div class="intro">%s</div>', event_intro_edit_event);
  	
  	$data = array(
  		'form_name'				=> 'event_group',
  		'form_action'			=> $this->page_link,
  		'action_name'			=> self::request_action,
  		'action_value'		=> self::action_group_check,
  		'header'					=> event_header_edit_group,
  		'intro'						=> $intro, 
  		'items'						=> $items,
  		'btn_ok'					=> event_btn_ok,
  		'btn_abort'				=> event_btn_abort,
  		'abort_location'	=> $this->page_link
  	);
  	return $parser->get($this->template_path.'backend.event.group.htt', $data);
  } // dlgEditGroup()
  
  /**
   * check the edited event group and create a new group 
   * or update the desired group
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
  		dbEventGroup::field_name		=> $_REQUEST[dbEventGroup::field_name],
  		dbEventGroup::field_desc		=> $_REQUEST[dbEventGroup::field_desc],
  		dbEventGroup::field_status	=> $_REQUEST[dbEventGroup::field_status]
  	);
  	
  	if ($group_id > 0) {
  		// existing group
  		$where = array(dbEventGroup::field_id => $group_id);
  		if (!$dbEventGroup->sqlUpdateRecord($data, $where)) {
  			$this->setError($dbEventGroup->getError());
  			return false;
  		}
  		$this->setMessage(sprintf(event_msg_group_updated, $group_id));
  	}
  	else {
  		// new group - check if name is already in use
  		$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s!='%s'", 
  										$dbEventGroup->getTableName(),
  										dbEventGroup::field_name,
  										$data[dbEventGroup::field_name],
  										dbEventGroup::field_status,
  										dbEventGroup::status_deleted);
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
  	global $parser;
  	$data = array(
  		'version'					=> sprintf('%01.2f', $this->getVersion()),
  		'img_url'					=> $this->img_url.'/kit_event_logo_424x283.jpg',
  		'release_notes'		=> file_get_contents(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.txt'),
  	);
  	return $parser->get($this->template_path.'backend.about.htt', $data);
  } // dlgAbout()
  
	
} // class eventBackend

?>