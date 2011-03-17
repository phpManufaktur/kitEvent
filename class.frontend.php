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

class eventFrontend {
	const request_action			= 'act';
	const request_event				= 'evt';
	const request_year				= 'y';
	const request_month				= 'm';
	const request_day					= 'd';
	const request_event_id		= 'id';
	
	const action_default 			= 'def';
	const action_day					= 'day';
	const action_event				= 'evt';
	const action_order				= 'ord';
	
	const event_day						= 'day';
		
	const param_view					= 'view';
	
	const view_week						= 'week';
	const view_month					= 'month';
	const view_quarter				= 'quarter';
	const view_all						= 'all';
	
	private $params = array(
		self::param_view				=> self::view_all 
	);
	
	private $template_path;
	private $page_link;
	
	public function __construct() {
		global $eventTools;
		$url = '';
		$_SESSION['FRONTEND'] = true;	
		$eventTools->getPageLinkByPageID(PAGE_ID, $url);
		$this->page_link = $url; 
		$this->template_path = WB_PATH.'/modules/kit_event/htt/'; 	
	} // __construct();
	
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
  	$this->error = sprintf('<div class="evt_error">[%s::%s - %s] %s</div>', basename($caller['file']), $caller['function'], $caller['line'], $error);
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
    $this->message = sprintf('<div class="evt_message">%s</div>', $message);
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
  	$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
   			$_REQUEST[$key] = $this->xssPrevent($value);
  		} 
  	}
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
    if (isset($_REQUEST[self::request_event])) $action = self::action_event;
  	switch ($action):
  	case self::action_order:
  		$result = $this->orderEvent();
  		break;
  	case self::action_event:
  		$result = $this->showEvent();
  		break;
  	default:
  		$result = $this->showOverview();
  		break;
  	endswitch;
  	
  	if ($this->isError()) $result = $this->getError();
		return $result;
  } // action
	
  public function orderEvent() {
  	return __METHOD__;
  } // orderEvent()
  
 	public function showOverview() {
 		return __METHOD__;
 	} // showOverview()
 	
 	public function showEvent() {
 		if (!isset($_REQUEST[self::request_event])) {
 			$this->setError(event_error_evt_invalid);
 			return false;
 		}
 		$event = strtolower(trim($_REQUEST[self::request_event]));
 		switch ($event):
 		case self::event_day:
 			$result = $this->getEventDay();
 			break;
 		default:
 			// nicht spezifiziertes Event
 			$this->setError(sprintf(event_error_evt_unspecified, $action));
 			return false;
 		endswitch;
 		return $result;
 	}
 	
 	public function getEventDay() {
 		global $dbEvent;
 		
 		if (!isset($_REQUEST[self::request_day]) || !isset($_REQUEST[self::request_month]) || !isset($_REQUEST[self::request_year])) {
 			$this->setError(event_error_evt_params_missing);
 			return false;
 		}
 		$month = (int) $_REQUEST[self::request_month];
 		$day = (int) $_REQUEST[self::request_day];
 		$year = (int) $_REQUEST[self::request_year];
 		$search_date_from = date('Y-m-d', mktime(23,59,59,$month,$day-1,$year));
 		$search_date_to = date('Y-m-d H:i:s', mktime(0,0,0,$month,$day+1,$year));
 		$SQL = sprintf( "SELECT %s FROM %s WHERE (%s BETWEEN '%s' AND '%s') AND %s='%s'",
 										dbEvent::field_id,
 										$dbEvent->getTableName(),
 										dbEvent::field_event_date_from, 
 										$search_date_from,
 										$search_date_to,
 										dbEvent::field_status,
 										dbEvent::status_active);
 		$events = array();
 		if (!$dbEvent->sqlExec($SQL, $events)) {
 			$this->setError($dbEvent->getError());
 			return false;
 		}
 		if (count($events) < 1) {
 			$this->setMessage(sprintf(event_msg_no_event_at_date, date(event_cfg_date_str, mktime(0,0,0,$month,$day,$year))));
 			return $this->getMessage();
 		}
 		$result = '';
 		foreach ($events as $event) {
 			$result .= $this->getEventID($event[dbEvent::field_id]);
 		}
 		return $result;
 	} // getEventDay()
  
 	public function getEventID($event_id) {
 		global $dbEvent;
 		global $dbEventItem;
 		global $dbEventGroup;
 		global $parser;
 		
 		$SQL = sprintf( 'SELECT * FROM %1$s, %2$s WHERE %1$s.%3$s = %2$s.%4$s AND %1$s.%5$s=\'%6$s\'',
 										$dbEvent->getTableName(),
 										$dbEventItem->getTableName(),
 										dbEvent::field_event_item,
 										dbEventItem::field_id,
 										dbEvent::field_id,
 										$event_id);
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
 		
 		$where = array(dbEventGroup::field_id => $event[dbEvent::field_event_group]);
 		$group = array();
 		if (!$dbEventGroup->sqlSelectRecord($where, $group)) {
 			$this->setError($dbEventGroup->getError());
 			return false;
 		}
 		if (count($group) > 0) {
 			$group_name = $group[0][dbEventGroup::field_name];
 			$group_desc = $group[0][dbEventGroup::field_desc];
 		}
 		else {
 			$group_name = '';
 			$group_desc = '';
 		}
 		$participants_max = ($event[dbEvent::field_participants_max] > 0) ? $event[dbEvent::field_participants_max] : event_text_participants_unlimited;
 		
 		$weekdays = explode(',', event_cfg_day_names);
 		$months = explode(',', event_cfg_month_names);
 		 
 		$data = array(
 		  'evt_headline'						=> $event[dbEventItem::field_title],
 			'evt_id'									=> sprintf('%03d', $event[dbEvent::field_id]),
 			'evt_group_name'					=> $group_name,
 			'evt_group_desc'					=> $group_desc,					
 			'evt_start_date'					=> date(event_cfg_date_str, strtotime($event[dbEvent::field_event_date_from])),
 			'evt_start_datetime'			=> date(event_cfg_datetime_str, strtotime($event[dbEvent::field_event_date_from])),
 			'evt_start_time'					=> date(event_cfg_time_str, strtotime($event[dbEvent::field_event_date_from])),
 			'evt_start_day'						=> date('j', strtotime($event[dbEvent::field_event_date_from])),
 			'evt_start_day_of_week'		=> trim($weekdays[date('w', strtotime($event[dbEvent::field_event_date_from]))]),
 			'evt_start_month'					=> trim($months[date('n', strtotime($event[dbEvent::field_event_date_from]))-1]), 
 			'evt_end_date'						=> date(event_cfg_date_str, strtotime($event[dbEvent::field_event_date_to])),
 			'evt_end_datetime'				=> date(event_cfg_datetime_str, strtotime($event[dbEvent::field_event_date_to])),
 			'evt_end_time'						=> date(event_cfg_time_str, strtotime($event[dbEvent::field_event_date_to])),
 			'evt_publish_start'				=> date(event_cfg_date_str, strtotime($event[dbEvent::field_publish_date_from])),
 			'evt_publish_end'					=> date(event_cfg_date_str, strtotime($event[dbEvent::field_publish_date_to])),
 			'evt_participants_max'		=> $participants_max,
 			'evt_participants_total'	=> $event[dbEvent::field_participants_total],
 			'evt_deadline'						=> date(event_cfg_date_str, strtotime($event[dbEvent::field_deadline])),
 			'evt_desc_short'					=> $event[dbEventItem::field_desc_short],
 			'evt_desc_long'						=> $event[dbEventItem::field_desc_long],
 			'evt_desc_link'						=> $event[dbEventItem::field_desc_link],
 			'evt_location'						=> $event[dbEventItem::field_location],
 			'evt_costs'								=> sprintf(event_cfg_currency, number_format($event[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator)),
 			'evt_order_link'					=> sprintf('%s?%s=%s&%s=%s', $this->page_link, self::request_action, self::action_order, self::request_event_id, $event_id)
 		);
 		
		return $parser->get($this->template_path.'frontend.event.detail.htt', $data); 		
 	} // getEventID()
 	
} // class eventFrontend

?>