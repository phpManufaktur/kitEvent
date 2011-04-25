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

class monthlyCalendar {
	
	const request_action		= 'act';
	const request_event			= 'evt';
	const request_year			= 'y';
	const request_month			= 'm';
	const request_day				= 'd';
	const request_event_id	= 'id';
	
	const action_show_month	= 'month';
	const action_default		= 'def';
	const action_show_list	= 'list';
	const action_order			= 'ord';
	
	const event_day					= 'day';
	
	private $error = '';
	private $template_path = '';
	private $page_link;
	private $response_link;
	
	const param_show_weeks		= 'show_weeks';
	const param_inactive_days	= 'inactive_days';
	const param_navigation		= 'navigation';
	const param_show_today		= 'show_today';
	const param_response_id		= 'response_id';
	const param_select_month	= 'month';
	const param_select_year		= 'year';
	const param_group					= 'group';
	const param_action				= 'action';
	const param_preset				= 'preset';
	
	private $params = array(
		self::param_show_weeks		=> true,
		self::param_inactive_days	=> true,
		self::param_navigation		=> true,
		self::param_show_today		=> true,
		self::param_response_id		=> -1,
		self::param_select_month	=> 0,
		self::param_select_year		=> 0,
		self::param_group					=> '',
		self::param_action				=> self::action_show_month,
		self::param_preset				=> 1
	);
	
	public function __construct() {
		global $eventTools;
		$this->template_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/';
		$eventTools->getUrlByPageID(PAGE_ID, $this->page_link);
		date_default_timezone_set(event_cfg_time_zone);
	} // __construct()
	
	public function getParams() {
		return $this->params;
	} // getParams()
	
	public function setParams($params = array()) {
		$this->params = $params;
		$this->template_path = WB_PATH.'/modules/kit_event/htt/'.$this->params[self::param_preset].'/'.KIT_EVT_LANGUAGE.'/';
		if (!file_exists($this->template_path)) {
			$this->setError(sprintf(event_error_preset_not_exists, '/modules/kit_event/htt/'.$this->params[self::param_preset].'/'.KIT_EVT_LANGUAGE.'/'));
			return false;
		}
	} // setParams()
	
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
	
  public function getTemplate($template, $template_data) {
  	global $parser;
  	try {
  		$result = $parser->get($this->template_path.$template, $template_data); 
  	} catch (Exception $e) {
  		$this->setError(sprintf(event_error_template_error, $template, $e->getMessage()));
  		return false;
  	}
  	return $result;
  } // getTemplate()
  
	public function action() {
		$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
   			$_REQUEST[$key] = $this->xssPrevent($value);
  		} 
  	}
  	
  	$action = (isset($this->params[self::param_action])) ? $this->params[self::param_action] : self::action_show_month;
  	if (isset($_REQUEST[self::request_action])) $action = $_REQUEST[self::request_action];
  		
  	switch ($action):
		case self::action_show_list:
			$result = $this->showList();
			break;
		case self::action_show_month:
  	default:
			$result = $this->showCalendar();  		
  		break;
  	endswitch;
		if ($this->isError()) $result = $this->getError();
		return $result;	
  } // action()
	
	private function getEvents($month, $year, $group='', $is_sheet=true) {
		global $dbEvent;
		global $dbEventGroup;
		
		$group = trim($group);
		$select_group = '';
		
		if (!empty($group)) {
			// ID der angegebenen Gruppe ermitteln
			$SQL = sprintf( "SELECT %s FROM %s WHERE %s='%s' AND %s='%s'",
											dbEventGroup::field_id,
											$dbEventGroup->getTableName(),
											dbEventGroup::field_name,
											$group,
											dbEventGroup::field_status,
											dbEventGroup::status_active	);
			if (!$dbEventGroup->sqlExec($SQL, $groups)) {
				$this->setError($dbEventGroup->getError());
				return false;
			}	
			if (count($groups) > 0) {
				$select_group = sprintf(" AND %s='%s'", dbEvent::field_event_group, $groups[0][dbEventGroup::field_id]);
			}
		}
		
		$ld = date ('j', mktime(0, 0, 0, $month+1,0, $year));
		$SQL = sprintf( "SELECT %s FROM %s WHERE (%s>='%s' AND %s<='%s')%s AND %s='%s'",
										($is_sheet) ? dbEvent::field_event_date_from : '*',
										$dbEvent->getTableName(),
										dbEvent::field_event_date_from,
										date('Y-m-d H:i:s', mktime(0,0,0, $month, 1, $year)),
										dbEvent::field_event_date_from,
										date('Y-m-d H:i:s', mktime(23,59,59, $month, $ld, $year)),
										$select_group,
										dbEvent::field_status,
										dbEvent::status_active);
		$events = array(); 
		if (!$dbEvent->sqlExec($SQL, $events)) {
			$this->setError($dbEvent->getError());
			return false;
		}
		if ($is_sheet) {
			$result = array();
			foreach ($events as $event) {
				$result[] = date('j', strtotime($event[dbEvent::field_event_date_from]));
			}
			return $result;
		}
		else {
			return $events;
		}
	} // getEvents()
	
	public function showCalendar() {
		global $eventTools;
		global $parser;
		
		if (($this->params[self::param_select_month] > 0) && ($this->params[self::param_select_month] < 13)) {
			$month = $this->params[self::param_select_month];
		}
		elseif ($this->params[self::param_select_month] < 0) {
			$month = date('n') + $this->params[self::param_select_month];
		} 
		elseif (($this->params[self::param_select_month] > 100) && ($this->params[self::param_select_month] < 112)) { 
			$month = date('n') + ($this->params[self::param_select_month] - 100);
		}
		else {
			$month = date('n');
		}
		
		if ($this->params[self::param_select_year] == 0) {
			// 0 == use actual year
			$year = date('Y');
		}
		elseif ($this->params[self::param_select_year] < 0) { 
			// substract value from actual year
			$year = date('Y') + $this->params[self::param_select_year];
		}
		elseif (($this->params[self::param_select_year] > 0) && ($this->params[self::param_select_year] < 100)) {
			$year = date('Y') + $this->params[self::param_select_year];
		}
		else {
			$year = $this->params[self::param_select_year];
		}
		
		if (isset($_REQUEST[self::request_month])) $month = $_REQUEST[self::request_month];
		if (isset($_REQUEST[self::request_year])) $year = $_REQUEST[self::request_year];
		
		$last_day_of_month = date ('j', mktime(0, 0, 0, $month+1,0, $year));
		$month_name = $this->getMonthName($month);
		
		if ($this->params[self::param_response_id] > 0) {
			$eventTools->getUrlByPageID($this->params[self::param_response_id], $this->response_link, true);
		}
		else {
			$this->response_link = $this->page_link;
		}
		
		// Events einlesen
		$events = $this->getEvents($month, $year, $this->params[self::param_group]);
		
		// Parameter fuer die Navigation
		if (($month-1) == 0) { 
			$prev_month = 12; $prev_year = $year-1;
		}
		else {
			$prev_month = $month-1;	$prev_year = $year;
		}
		if (($month+1) == 13)	{
			$next_month = 1;	$next_year = $year+1;
		}
		else {
			$next_month = $month+1;	$next_year = $year;
		}
		// navigation
		$navigation = array(
			'prev_link'					=> sprintf('%s?%s=%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_show_month,	self::request_month, $prev_month,	self::request_year,	$prev_year),
			'prev_hint'					=> event_hint_previous_month,
			'prev_text'					=> event_cfg_cal_prev_month,
			'month_year'				=> sprintf('%s %d', $month_name, $year),
			'next_link'					=> sprintf('%s?%s=%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_show_month,	self::request_month,	$next_month,	self::request_year,	$next_year),
			'next_hint'					=> event_hint_next_month,
			'next_text'					=> event_cfg_cal_next_month
		);
		
		$head = array(
			'0'	=> $this->getDayOfWeekName(0, 2, true),
			'1'	=> $this->getDayOfWeekName(1, 2, true),
			'2'	=> $this->getDayOfWeekName(2, 2, true),
			'3'	=> $this->getDayOfWeekName(3, 2, true),
			'4'	=> $this->getDayOfWeekName(4, 2, true),
			'5'	=> $this->getDayOfWeekName(5, 2, true),
			'6'	=> $this->getDayOfWeekName(6, 2, true),
		);
			
		// step through the month...
		$start_day_of_week = date('w', mktime(0,0,0, $month, 1, $year));
		$start = true;
		$i = 1;
		$dow = 1;
		$week = array();
		$week['week'] = date('W', mktime(0,0,0,$month,$i, $year));
		$complete = false;
		
		// should indicate the actual day?
		$check_today = ($this->params[self::param_show_today] && (mktime(0,0,0,$month,1,$year) == mktime(0,0,0,date('n'),1,date('Y')))) ? true : false;
		
		$mon = array();
		while ($i < 50) {
			// Woche schreiben
			if (!$start && ($dow == 1)) {
				$mon[] = $week;
				if ($complete) break;
				$week = array();
				$week['week'] = date('W', mktime(0,0,0,$month,$i, $year));
			}
			// Beim Start bis zum richtigen Wochentag durchlaufen
			if ($start) {
				if ($start_day_of_week == $dow) {
					$start = false;
				}
				else {
					if ($this->params[self::param_inactive_days]) {
						$x = $dow - ($start_day_of_week -1);
						$week[$dow]['date'] = date('j', mktime(0,0,0,$month,$x,$year));
						$week[$dow]['type'] = 'cms_day_inactive';
					}
					else {
						$week[$dow]['date'] = '';
						$week[$dow]['type'] = 'cms_day_hidden';
					}
					$dow++;
					if ($dow > 6) $dow = 0;
					continue;
				}
			} 
			// job is done, add the remaining cells to the row
			if (!$complete) {
				if (in_array($i, $events)) {
					// es gibt eine oder mehrere Veranstaltungen
					$week[$dow]['date'] = $i;
					$week[$dow]['link'] = sprintf('%s?%s=%s&%s=%s&%s=%s&%s=%s', $this->response_link,	self::request_event, self::event_day,	self::request_month, $month, self::request_day,	$i,	self::request_year,	$year);
					$week[$dow]['hint'] = event_hint_click_for_detail;
					$week[$dow]['type'] = 'cms_day_event';
				}
				else {
					// normaler Tag
					$week[$dow]['date'] = $i;
					$week[$dow]['type'] = ($check_today && ($i == date('j')))	? 'cms_day_today' : '';
				}				
			}
			elseif ($this->params[self::param_inactive_days]) {
				$week[$dow]['date'] = date('j', mktime(0,0,0, $month, $i, $year));
				$week[$dow]['type'] = 'cms_day_inactive';
			}
			else {
				$week[$dow]['date'] = '';
			}
			$i++;
			if ($i > $last_day_of_month) $complete = true;
			$dow++;
			if ($dow > 6) $dow = 0;
		}
		
		// show complete calendar sheet
		$data = array(
			'show_weeks'				=> ($this->params[self::param_show_weeks]) ? 1 : 0,
			'show_navigation'		=> ($this->params[self::param_navigation]) ? 1 : 0,
			'navigation'				=> $navigation,
			'head'							=> $head,
			'month'							=> $mon
		);
		return $this->getTemplate('calendar.htt', $data); 	
	} // showCalendar()
	
	private function getMonthName($month, $length=-1, $uppercase=false) {
		$month_names = explode(',', event_cfg_month_names);
		if (isset($month_names[$month-1])) {
			$month_name = trim($month_names[$month-1]);
			if ($length > 0) $month_name = substr($month_name, 0, $length);
			if ($uppercase) $month_name = strtoupper($month_name);
			return $month_name;
		}
		else {
			$this->setError(sprintf(event_error_cal_month_def_invalid, $month));
			return false;
		}
	} // getMonthName()
	
	private function getDayOfWeekName($day_of_week, $length=-1, $uppercase=false) {
		$day_names = explode(',', event_cfg_day_names);
		if (isset($day_names[$day_of_week])) {
			$day_name = trim($day_names[$day_of_week]);
			if ($length > 0) $day_name = substr($day_name, 0, $length);
			if ($uppercase) $day_name = strtoupper($day_name);
			return $day_name;
		}
		else {
			$this->setError(sprintf(event_error_cal_dayofweek_def_invalid, $day_of_week));
			return false;
		}
	} // getDayName()
	
	public function showList() {
		global $eventTools;
		global $dbEvent;
		global $dbEventItem;
		global $dbEventGroup;
		
		if (($this->params[self::param_select_month] > 0) && ($this->params[self::param_select_month] < 12)) {
			$month = $this->params[self::param_select_month];
		}
		elseif ($this->params[self::param_select_month] < 0) {
			$month = date('n') + $this->params[self::param_select_month];
		} 
		elseif (($this->params[self::param_select_month] > 100) && ($this->params[self::param_select_month] < 112)) { 
			$month = date('n') + ($this->params[self::param_select_month] - 100);
		}
		else {
			$month = date('n');
		}
		
		if ($this->params[self::param_select_year] == 0) {
			// 0 == use actual year
			$year = date('Y');
		}
		elseif ($this->params[self::param_select_year] < 0) { 
			// substract value from actual year
			$year = date('Y') + $this->params[self::param_select_year];
		}
		elseif (($this->params[self::param_select_year] > 0) && ($this->params[self::param_select_year] < 100)) {
			$year = date('Y') + $this->params[self::param_select_year];
		}
		else {
			$year = $this->params[self::param_select_year];
		}
		
		if ($this->params[self::param_response_id] > 0) {
			$eventTools->getUrlByPageID($this->params[self::param_response_id], $this->response_link, true);
		}
		else {
			$this->response_link = $this->page_link;
		}
		
		// Events einlesen
		$events = $this->getEvents($month, $year, $this->params[self::param_group], false);
		
		$items = array();
		foreach ($events as $event) {
			$SQL = sprintf( "SELECT * FROM %s WHERE %s='%s'", $dbEventItem->getTableName(), dbEventItem::field_id, $event[dbEvent::field_event_item]);
			$eventItem = array();
			if (!$dbEventItem->sqlExec($SQL, $eventItem)) {
				$this->setError($dbEventItem->getError());
				return false;
			}
			if (count($eventItem) < 0) {
				$this->setError(sprintf(event_error_id_invalid, $event[dbEvent::field_event_item]));
				return false;
			}
			$eventItem = $eventItem[0];
			
			$SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbEventGroup->getTableName(), dbEventGroup::field_id, $event[dbEvent::field_event_group]);
			$eventGroup = array();
			if (!$dbEventGroup->sqlExec($SQL, $eventGroup)) {
				$this->setError($dbEventGroup->getError());
				return false;
			}
			$eventGroup = (count($eventGroup) > 0) ? $eventGroup[0] : $dbEventGroup->getFields();
			
			$eItem = array(
				'headline'			=> $eventItem[dbEventItem::field_title],
				'desc_short'		=> $eventItem[dbEventItem::field_desc_short],
				'desc_long'			=> $eventItem[dbEventItem::field_desc_long],
				'desc_link'			=> $eventItem[dbEventItem::field_desc_link],
				'location'			=> $eventItem[dbEventItem::field_location],
				'costs'					=> number_format($eventItem[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator)
			);
			
			$start_date = strtotime($event[dbEvent::field_event_date_from]);
			$end_date = strtotime($event[dbEvent::field_event_date_to]);
			$day_names = explode(',', event_cfg_day_names);
			$month_names = explode(',', event_cfg_month_names);
			$items[] = array(
				'event'								=> $eItem,
				'start_day'						=> date('j', $start_date),
				'start_day_zero'			=> date('d', $start_date),
				'start_day_name'			=> $day_names[date('w', $start_date)],
				'start_day_name_2'		=> substr($day_names[date('w', $start_date)], 1, 2),
				'start_month'					=> date('n', $start_date),
				'start_month_zero'		=> date('m', $start_date),
				'start_month_name'		=> $month_names[date('n', $start_date)-1],
				'start_month_name_3'	=> substr($month_names[date('n', $start_date)-1], 1, 3),
				'start_year'					=> date('Y', $start_date),
				'start_time'					=> date(event_cfg_time_str, $start_date),

				'end_day'							=> date('j', $end_date),
				'end_day_zero'				=> date('d', $end_date),
				'end_day_name'				=> $day_names[date('w', $end_date)],
				'end_day_name_2'			=> substr($day_names[date('w', $end_date)], 1, 2),
				'end_month'						=> date('n', $end_date),
				'end_month_zero'			=> date('m', $end_date),
				'end_month_name'			=> $month_names[date('n', $end_date)-1],
				'end_month_name_3'		=> substr($month_names[date('n', $end_date)-1], 1, 3),
				'end_year'						=> date('Y', $end_date),
				'end_time'						=> date(event_cfg_time_str, $end_date),
				
				'participants_max'		=> $event[dbEvent::field_participants_max],
				'participants_total'	=> $event[dbEvent::field_participants_total],
				'participants_free'		=> $event[dbEvent::field_participants_max]-$event[dbEvent::field_participants_total],

				'deadline'						=> date(event_cfg_date_str, strtotime($event[dbEvent::field_deadline])),	
			
				'group_name'					=> $eventGroup[dbEventGroup::field_name],
				'group_desc'					=> $eventGroup[dbEventGroup::field_desc],
			
				'link_order'					=> sprintf('%s?%s=%s&%s=%s', $this->response_link, self::request_action, self::action_order, self::request_event_id, $event[dbEvent::field_id]),
				'link_day'						=> sprintf('%s?%s=%s&%s=%s&%s=%s&%s=%s', $this->response_link,	self::request_event, self::event_day,	self::request_month, $month, self::request_day,	date('j', $start_date),	self::request_year,	$year)
			);
		}
		
		$data = array(
			'dates' => $items
		);
		return $this->getTemplate('calendar.list.htt', $data);
	} // showList()
	
} // class monthlyCalendar