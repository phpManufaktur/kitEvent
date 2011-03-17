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
	
	const action_show_month	= 'sm';
	const action_default		= 'def';
	
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
	
	private $params = array(
		self::param_show_weeks		=> true,
		self::param_inactive_days	=> true,
		self::param_navigation		=> true,
		self::param_show_today		=> true,
		self::param_response_id		=> -1
	);
	
	public function __construct() {
		global $eventTools;
		$this->template_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/';
		$eventTools->getUrlByPageID(PAGE_ID, $this->page_link);
	} // __construct()
	
	public function getParams() {
		return $this->params;
	} // getParams()
	
	public function setParams($params = array()) {
		$this->params = $params;
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
	
	public function action() {
		$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
  			// Sonderfall: Value Felder der Konfiguration werden durchnummeriert und duerfen HTML enthalten...
  			if (strpos($key, dbEventCfg::field_value) == false) {
    			$_REQUEST[$key] = $this->xssPrevent($value);
  			}
  		} 
  	}
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
  	switch ($action):
		case self::action_show_month:
  	default:
			$result = $this->showCalendar();  		
  		break;
  	endswitch;
		if ($this->isError()) $result = $this->getError();
		return $result;	
  } // action()
	
	private function getEvents($month, $year) {
		global $dbEvent;
		$ld = date ('j', mktime(0, 0, 0, $month+1,0, $year));
		$SQL = sprintf( "SELECT %s FROM %s WHERE %s>='%s' AND %s<='%s' AND %s='%s'",
										dbEvent::field_event_date_from,
										$dbEvent->getTableName(),
										dbEvent::field_event_date_from,
										date('Y-m-d H:i:s', mktime(0,0,0, $month, 1, $year)),
										dbEvent::field_event_date_to,
										date('Y-m-d H:i:s', mktime(23,59,59, $month, $ld, $year)),
										dbEvent::field_status,
										dbEvent::status_active);
		$events = array();
		if (!$dbEvent->sqlExec($SQL, $events)) {
			$this->setError($dbEvent->getError());
			return false;
		}
		$result = array();
		foreach ($events as $event) {
			$result[] = date('j', strtotime($event[dbEvent::field_event_date_from]));
		}
		return $result;
	} // getEvents()
	
	public function showCalendar() {
		global $parser;
		global $eventTools;
		
		$month = isset($_REQUEST[self::request_month]) ? $_REQUEST[self::request_month] : date('n');
		$year = isset($_REQUEST[self::request_year]) ? $_REQUEST[self::request_year] : date('Y');
		
		$last_day_of_month = date ('j', mktime(0, 0, 0, $month+1,0, $year));
		$month_name = $this->getMonthName($month);
		
		if ($this->params[self::param_response_id] > 0) {
			$eventTools->getUrlByPageID($this->params[self::param_response_id], $this->response_link);
		}
		else {
			$this->response_link = $this->page_link;
		}
		
		// Events einlesen
		$events = $this->getEvents($month, $year);
		
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
		// month navigation
		if ($this->params[self::param_navigation]) {
			$data = array(
				'link_month_prev'		=> sprintf(	'<a href="%s?%s=%s&%s=%s&%s=%s" title="%s">%s</a>',
																				$this->page_link,
																				self::request_action,
																				self::action_show_month,
																				self::request_month,
																				$prev_month,
																				self::request_year,
																				$prev_year,
																				event_hint_previous_month,
																				event_cfg_cal_prev_month),
				'month_name'				=> sprintf('%s %d', $month_name, $year),
				'link_month_next'		=> sprintf(	'<a href="%s?%s=%s&%s=%s&%s=%s" title="%s">%s</a>',
																				$this->page_link,
																				self::request_action,
																				self::action_show_month,
																				self::request_month,
																				$next_month,
																				self::request_year,
																				$next_year,
																				event_hint_next_month,
																				event_cfg_cal_next_month)
			);
		}
		else {
			$data = array(
				'link_month_prev'		=> '',
				'month_name'				=> sprintf('%s %d', $month_name, $year),
				'link_month_next'		=> ''
			);
		}
		$template = ($this->params[self::param_show_weeks]) ? 'calendar.8.nav.htt' : 'calendar.7.nav.htt';
		$items = $parser->get($this->template_path.$template, $data);
		
		$template = ($this->params[self::param_show_weeks]) ? 'calendar.8.row.htt' : 'calendar.7.row.htt';
		$row = new Dwoo_Template_File($this->template_path.$template);
		
		// header with weekdays
		$data = array(
			'row_class'		=> 'cms_head',
			'week'				=> '',
			'day_0'				=> $this->getDayOfWeekName(0, 2, true),
			'day_type_0'	=> 'cms_head_sunday',
			'day_1'				=> $this->getDayOfWeekName(1, 2, true),
			'day_type_1'	=> 'cms_head_day',
			'day_2'				=> $this->getDayOfWeekName(2, 2, true),
			'day_type_2'	=> 'cms_head_day',
			'day_3'				=> $this->getDayOfWeekName(3, 2, true),
			'day_type_3'	=> 'cms_head_day',
			'day_4'				=> $this->getDayOfWeekName(4, 2, true),
			'day_type_4'	=> 'cms_head_day',
			'day_5'				=> $this->getDayOfWeekName(5, 2, true),
			'day_type_5'	=> 'cms_head_day',
			'day_6'				=> $this->getDayOfWeekName(6, 2, true),
			'day_type_6'	=> 'cms_head_saturday'			
		);
		$items .= $parser->get($row, $data);
		
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

		while ($i < 50) {
			// Woche schreiben
			if (!$start && ($dow == 1)) {
				$items .= $parser->get($row, $week);
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
						$week['day_'.$dow] = date('j', mktime(0,0,0,$month,$x,$year));
						$week['day_type_'.$dow] = 'cms_day_inactive';
					}
					$dow++;
					if ($dow > 6) $dow = 0;
					continue;
				}
			} 
			// job is done, add the remaining cells to the row
			if (!$complete) {
				if ($check_today && ($i == date('j')))	$week['day_type_'.$dow] = 'cms_day_today';
				if (in_array($i, $events)) {
					// es gibt eine oder mehrere Veranstaltungen
					$week['day_'.$dow] = sprintf(	'<a href="%s?%s=%s&%s=%s&%s=%s&%s=%s" title="%s">%s</a>',
																				$this->response_link,
																				self::request_event,
																				self::event_day,
																				self::request_month,
																				$month,
																				self::request_day,
																				$i,
																				self::request_year,
																				$year,
																				event_hint_click_for_detail,
																				$i);
					$week['day_type_'.$dow] = 'cms_day_event';
				}
				else {
					// normaler Tag
					$week['day_'.$dow] = $i;
				}				
			}
			elseif ($this->params[self::param_inactive_days]) {
				$week['day_'.$dow] = date('j', mktime(0,0,0, $month, $i, $year));
				$week['day_type_'.$dow] = 'cms_day_inactive';
			}
			$i++;
			if ($i > $last_day_of_month) $complete = true;
			$dow++;
			if ($dow > 6) $dow = 0;
		}
		
		// show complete calendar sheet
		$data = array('items' => $items);
		$template = ($this->params[self::param_show_weeks]) ? 'calendar.8.htt' : 'calendar.7.htt';
		return $parser->get($this->template_path.$template, $data); 	
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
	
} // class monthlyCalendar