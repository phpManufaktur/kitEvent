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
require_once(WB_PATH.'/include/captcha/captcha.php');
require_once(WB_PATH.'/framework/class.wb.php');


class eventFrontend {
	const request_action				= 'act';
	const request_event					= 'evt';
	const request_year					= 'y';
	const request_month					= 'm';
	const request_day						= 'd';
	const request_event_id			= 'id';
	const request_event_detail	= 'det';
	
	const request_must_fields	= 'mf';
	
	const must_captcha				= 'captcha';
	const must_first_name			= 'first_name';
	const must_last_name			= 'last_name';
	const must_street					= 'street';
	const must_zip						= 'zip';
	const must_city						= 'city';
	const must_email					= 'email';
	const must_phone					= 'phone';
	const must_terms					= 'terms_and_conditions';					
	
	const request_title				= 'title';
	const request_first_name	= 'fn';
	const request_last_name		= 'ln';
	const request_company			= 'com';
	const request_street			= 'str';
	const request_zip					= 'zip';
	const request_city				= 'cty';
	const request_email				= 'eml';
	const request_phone				= 'phn';
	const request_best_time		= 'bt';
	const request_confirm			= 'con';
	const request_terms				= 'trm';
	const request_message			= 'msg';
	
	const action_default 			= 'def';
	const action_day					= 'day';
	const action_event				= 'evt';
	const action_order				= 'ord';
	const action_order_check	= 'chk';
	
	const param_view					= 'view';
	const param_preset				= 'preset';
	const param_detail				= 'detail';
	const param_group					= 'group';
	
	const view_id							= 'id';
	const view_day						= 'day';
	const view_week						= 'week';
	const view_month					= 'month';
	const view_active					= 'active';
	
	private $params = array(
		self::param_view				=> self::view_active,
		self::param_preset			=> 1, 
		self::param_detail			=> false,
		self::param_group				=> ''
	);
	
	private $template_path;
	private $page_link;
	
	public function __construct() {
		global $eventTools;
		$url = '';
		$_SESSION['FRONTEND'] = true;	
		$eventTools->getPageLinkByPageID(PAGE_ID, $url);
		$this->page_link = $url; 
		$this->template_path = WB_PATH.'/modules/kit_event/htt/'.$this->params[self::param_preset].'/'.KIT_EVT_LANGUAGE.'/'; 	
		date_default_timezone_set(event_cfg_time_zone);
	} // __construct();
	
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
		return true;
	} // setParams()
	
	/**
    * Set $this->error to $error
    * 
    * @param STR $error
    */
  public function setError($error) {
  	$debug = debug_backtrace();
    $caller = next($debug);
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
  
  /**
   * Action Handler der class.frontend.php
   * Diese Funktion wird generell von aussen aufgerufen und steuert die Klasse.
   * @return STR result dialog
   */
  public function action() { 
  	if ($this->isError()) return $this->getError();
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
  	case self::action_order_check:
  		$result = $this->checkOrder();
  		break;
  	case self::action_event:
  		$result = $this->showEvent();
  		break;
  	default:
  		$result = $this->showEvent($this->params[self::param_view]);
  		break;
  	endswitch;
  	
  	if ($this->isError()) $result = $this->getError();
		return $result;
  } // action
	
  public function getMondayOfWeekDate($date) {
  	$dow = date('w', $date);
  	if ($dow == 0) $dow = 7;
  	$sub = $dow-1;
  	return mktime(0,0,0,date('n', $date),date('j', $date)-$sub,date('Y',$date));
  }
  
  /**
   * Daten fuer die angegebene Event ID auslesen und zusaetzlich ein Array mit Informationen
   * fuer die Ausgabe ueber beliebige Templates erzeugen
   * @param INT $event_id
   * @param REFERENCE ARRAY $event_data
   * @param REFERENCE ARRAY $parser_data
   * @return BOOL true on success
   */
  private function getEventData($event_id, &$event_data=array(), &$parser_data=array()) {
  	global $dbEvent;
  	global $dbEventItem;
  	global $dbEventGroup;
  	
  	$SQL = sprintf( 'SELECT * FROM %1$s, %2$s WHERE %1$s.%3$s = %2$s.%4$s AND %1$s.%5$s=\'%6$s\'',
 										$dbEvent->getTableName(),
 										$dbEventItem->getTableName(),
 										dbEvent::field_event_item,
 										dbEventItem::field_id,
 										dbEvent::field_id,
 										$event_id);
 		$event_data = array();
 		if (!$dbEvent->sqlExec($SQL, $event_data)) {
 			$this->setError($dbEvent->getError());
 			return false;
 		}
 		if (count($event_data) < 1) {
 			$this->setError(sprintf(event_error_id_invalid, $event_id));
 			return false;
 		}
 		$event_data = $event_data[0];
 		$where = array(dbEventGroup::field_id => $event_data[dbEvent::field_event_group]);
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
 		if ($event_data[dbEvent::field_participants_max] > 0) {
 			$participants_max = $event_data[dbEvent::field_participants_max];
 			$participants_free = (($x = $event_data[dbEvent::field_participants_max]-$event_data[dbEvent::field_participants_total]) > 0) ? $x : event_text_fully_booked; 	
 		}
 		else {
 			$participants_max = event_text_participants_unlimited;
 			$participants_free = event_text_participants_free;
 		}
 		
 		$weekdays = explode(',', event_cfg_day_names);
 		$months = explode(',', event_cfg_month_names);
  	
  	$parser_data = array( 
  		'evt_headline'						=> $event_data[dbEventItem::field_title],
 			'evt_id'									=> sprintf('%03d', $event_data[dbEvent::field_id]),
 			'evt_group_name'					=> $group_name,
 			'evt_group_desc'					=> $group_desc,					
 			'evt_start_date'					=> date(event_cfg_date_str, strtotime($event_data[dbEvent::field_event_date_from])),
 			'evt_start_datetime'			=> date(event_cfg_datetime_str, strtotime($event_data[dbEvent::field_event_date_from])),
 			'evt_start_time'					=> date(event_cfg_time_str, strtotime($event_data[dbEvent::field_event_date_from])),
 			'evt_start_day'						=> date('j', strtotime($event_data[dbEvent::field_event_date_from])),
 			'evt_start_day_of_week'		=> trim($weekdays[date('w', strtotime($event_data[dbEvent::field_event_date_from]))]),
 			'evt_start_month'					=> trim($months[date('n', strtotime($event_data[dbEvent::field_event_date_from]))-1]), 
 			'evt_end_date'						=> date(event_cfg_date_str, strtotime($event_data[dbEvent::field_event_date_to])),
 			'evt_end_datetime'				=> date(event_cfg_datetime_str, strtotime($event_data[dbEvent::field_event_date_to])),
 			'evt_end_time'						=> date(event_cfg_time_str, strtotime($event_data[dbEvent::field_event_date_to])),
 			'evt_publish_start'				=> date(event_cfg_date_str, strtotime($event_data[dbEvent::field_publish_date_from])),
 			'evt_publish_end'					=> date(event_cfg_date_str, strtotime($event_data[dbEvent::field_publish_date_to])),
 			'evt_participants_max'		=> $participants_max,
 			'evt_participants_total'	=> $event_data[dbEvent::field_participants_total],
 			'evt_participants_free'		=> $participants_free,
 			'evt_deadline'						=> date(event_cfg_date_str, strtotime($event_data[dbEvent::field_deadline])),
 			'evt_desc_short'					=> $event_data[dbEventItem::field_desc_short],
 			'evt_desc_long'						=> $event_data[dbEventItem::field_desc_long],
 			'evt_desc_link'						=> $event_data[dbEventItem::field_desc_link],
 			'evt_location'						=> $event_data[dbEventItem::field_location],
 			'evt_costs'								=> sprintf(event_cfg_currency, number_format($event_data[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator)),
 			'evt_order_link'					=> sprintf('%s?%s=%s&%s=%s', $this->page_link, self::request_action, self::action_order, self::request_event_id, $event_id),
  		'evt_detail_link'					=> sprintf('%s?%s=%s&%s=%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_event, self::request_event_id, $event_id, self::request_event, self::view_id, self::request_event_detail, 1),
  		'evt_start_link'					=> $this->page_link
 		);
 		return true;
  } // getEventFields()
  
  /**
   * Anmeldung zu einem Event pruefen, ggf. wieder Anmeldedialog mit Hinweisen
   * anzeigen. Wenn OK, Daten uebernehmen, Zaehler aktualisieren und E-Mails 
   * an Besteller sowie an den Seitenbetreiber versenden
   * @return FALSE on error or DIALOG/MESSAGE on success  
   */
  public function checkOrder() { 
  	global $eventTools;
  	global $dbEvent;
  	global $dbEventOrder;
  	global $wb;
  	
  	if (!isset($_REQUEST[self::request_event_id]) && !isset($_REQUEST[dbEvent::field_id])) {
  		$this->setError(event_error_evt_invalid);
  		return false;
  	}
  	$event_id = (isset($_REQUEST[self::request_event_id])) ? (int) $_REQUEST[self::request_event_id] : (int) $_REQUEST[dbEvent::field_id];
  	
  	if (!isset($_REQUEST[self::request_must_fields])) {
  		$this->setError(event_error_must_fields_missing);
  		return false;
  	}
  	$mf = strtolower($_REQUEST[self::request_must_fields]);
  	$mf = str_replace(' ', '', $mf);
  	$must_fields = explode(',', $mf);
  	if (!in_array(self::must_email, $must_fields)) $must_fields[] = self::must_email;
  	
  	$message = '';
  	foreach ($must_fields as $must_field) {
  		switch ($must_field):
  		case self::must_captcha:
		  	if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) $message .= event_msg_captcha_invalid;
				break;
  		case self::must_city:
  			if (!isset($_REQUEST[self::request_city]) || (strlen($_REQUEST[self::request_city]) < 4)) $message .= event_msg_must_city;
  			break;		
  		case self::must_email:
		  	if (!isset($_REQUEST[self::request_email]) || !$eventTools->validateEMail($_REQUEST[self::request_email])) {
					$message .= sprintf(event_msg_invalid_email, $_REQUEST[self::request_email]);
				}
  			break;
  		case self::must_first_name:
  			if (!isset($_REQUEST[self::request_first_name]) || empty($_REQUEST[self::request_first_name])) $message .= event_msg_must_first_name;
  			break;
  		case self::must_last_name:
  			if (!isset($_REQUEST[self::request_last_name]) || empty($_REQUEST[self::request_last_name])) $message .= event_msg_must_last_name;
  			break;
			case self::must_phone:
  			if (!isset($_REQUEST[self::request_phone]) || empty($_REQUEST[self::request_phone])) $message .= event_msg_must_phone;
  			break;
  		case self::must_street:
  			if (!isset($_REQUEST[self::request_street]) || empty($_REQUEST[self::request_street])) $message .= event_msg_must_street;
  			break;
  		case self::must_terms:
  			if (!isset($_REQUEST[self::request_terms])) $message .= event_msg_must_terms_and_conditions;
  			break;
  		case self::must_zip:
  			if (!isset($_REQUEST[self::request_zip]) || empty($_REQUEST[self::request_zip])) $message .= event_msg_must_zip;
  			break;
  		endswitch;
  	}
  	if (!empty($message)) {
  		$this->setMessage($message);
  		return $this->orderEvent();
  	}
  	
		// ok - Daten sichern und Bestaetigungsmails versenden	
  	$orderData = array(
  		dbEventOrder::field_best_time			=> (isset($_REQUEST[self::request_best_time])) ? $_REQUEST[self::request_best_time] : '',
  		dbEventOrder::field_city					=> (isset($_REQUEST[self::request_city])) ? $_REQUEST[self::request_city] : '',
  		dbEventOrder::field_company				=> (isset($_REQUEST[self::request_company])) ? $_REQUEST[self::request_company] : '',
  		dbEventOrder::field_confirm_order	=> (isset($_REQUEST[self::request_confirm])) ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00',
  		dbEventOrder::field_email					=> strtolower($_REQUEST[self::request_email]),
  		dbEventOrder::field_event_id			=> $event_id,
  		dbEventOrder::field_first_name		=> (isset($_REQUEST[self::request_first_name])) ? $_REQUEST[self::request_first_name] : '',
  		dbEventOrder::field_last_name			=> (isset($_REQUEST[self::request_last_name])) ? $_REQUEST[self::request_last_name] : '',
  		dbEventOrder::field_message				=> (isset($_REQUEST[self::request_message])) ? $_REQUEST[self::request_message] : '',
  		dbEventOrder::field_order_date		=> date('Y-m-d H:i:s'),
  		dbEventOrder::field_phone					=> (isset($_REQUEST[self::request_phone])) ? $_REQUEST[self::request_phone] : '',
  		dbEventOrder::field_street				=> (isset($_REQUEST[self::request_street])) ? $_REQUEST[self::request_street] : '',
  		dbEventOrder::field_title					=> (isset($_REQUEST[self::request_title])) ? $_REQUEST[self::request_title] : '',
  		dbEventOrder::field_zip						=> (isset($_REQUEST[self::request_zip])) ? $_REQUEST[self::request_zip] : '' 
  	);
  	
  	$order_id = -1;
  	if (!$dbEventOrder->sqlInsertRecord($orderData, $order_id))  {
  		$this->setError($dbEventOrder->getError());
  		return false;
  	}
 		
  	// wenn eine Anmeldung erfolgt ist, muss der Zaehler bei dbEvent erhoeht werden!
  	if (false !== ($dt = $orderData[dbEventOrder::field_confirm_order])) {
  		/* dieser "kurze" Aufruf zur Aktualisierung loest einen E_WARNING in der WB class.database.php aus !!!
  		$SQL = sprintf( "UPDATE %s SET %s=%s+'1' WHERE %s='%s'", 
  										$dbEvent->getTableName(),
  										dbEvent::field_participants_total,
  										dbEvent::field_participants_total,
  										dbEvent::field_id,
  										$event_id);
  		if (!$dbEvent->sqlExec($SQL, $result)) {
  			$this->setError($dbEvent->getError());
  			return false;
  		}
  		*/
  		$SQL = sprintf(	"SELECT %s FROM %s WHERE %s='%s'",
  										dbEvent::field_participants_total,
  										$dbEvent->getTableName(),
  										dbEvent::field_id,
  										$event_id);
  		if (!$dbEvent->sqlExec($SQL, $counter)) {
  			$this->setError($dbEvent->getError());
  			return false;
  		}
  		if (count($counter) < 1) {
  			$this->setError(sprintf(event_error_id_invalid, $event_id));
  			return false;
  		}
  		$where = array(dbEvent::field_id => $event_id);
  		$update = array(dbEvent::field_participants_total => $counter[0][dbEvent::field_participants_total]+1);
  		if (!$dbEvent->sqlUpdateRecord($update, $where)) {
  			$this->setError($dbEvent->getError());
  			return false;
  		}
  	}
  	
  	// Bestaetigungsmail an den Kunden
  	$data = array(
  		'order_title'							=> $orderData[dbEventOrder::field_title],
  		'order_first_name'				=> $orderData[dbEventOrder::field_first_name],
  		'order_last_name'					=> $orderData[dbEventOrder::field_last_name],
  		'order_company'						=> $orderData[dbEventOrder::field_company],
  		'order_street'						=> $orderData[dbEventOrder::field_street],
  		'order_zip'								=> $orderData[dbEventOrder::field_zip],
  		'order_city'							=> $orderData[dbEventOrder::field_city],
  		'order_email'							=> $orderData[dbEventOrder::field_email],
  		'order_phone'							=> $orderData[dbEventOrder::field_phone],
  		'order_best_time'					=> $orderData[dbEventOrder::field_best_time],
  		'order_message'						=> $orderData[dbEventOrder::field_message],
  		'order_confirm'						=> (!strtotime($orderData[dbEventOrder::field_confirm_order])) ? strtoupper(event_text_not_confirmed) : strtoupper(event_text_confirmed),
 		);

 		if (!$this->getEventData($event_id, $event, $event_parser)) return false;
 		$data = array_merge($data, $event_parser);
 		
 		if (false == ($body = $this->getTemplate('mail.confirm.participant.htt', $data))) return false;
  	if (!$wb->mail(SERVER_EMAIL, $orderData[dbEventOrder::field_email], $event[dbEventItem::field_title], $body)) {
			$this->setError(sprintf(event_error_send_email, $orderData[dbEventOrder::field_email]));
			return false;
	  }
  	
	  // Datensatz aktualisieren
	  $update = array(dbEventOrder::field_send_mail => date('Y-m-d H:i:s'));
	  $where = array(dbEventOrder::field_id => $order_id);
	  if (!$dbEventOrder->sqlUpdateRecord($update, $where)) {
	  	$this->setError($dbEventOrder->getError());
	  	return false;
	  }
	  
	  // E-Mail an Seitenbetreiber
	  if (false == ($body = $this->getTemplate('mail.confirm.admin.htt', $data))) return false;
		if (!$wb->mail(SERVER_EMAIL, SERVER_EMAIL, $event[dbEventItem::field_title], $body)) {
			$this->setError(sprintf(event_error_send_email, $orderData[dbEventOrder::field_email]));
			return false;
	  }
	  
  	return $this->getTemplate('frontend.event.order.confirm.htt', $data);
  } // checkOrder()
  
  /**
   * Bestell- und Kontaktdialog fuer die Events
   * @return STR dialog
   */
  public function orderEvent() {
  	global $dbEvent;
  	global $dbEventItem;
  	global $dbEventGroup;
  	
  	if (!isset($_REQUEST[self::request_event_id]) && !isset($_REQUEST[dbEvent::field_id])) {
  		$this->setError(event_error_evt_invalid);
  		return false;
  	}
  	$event_id = (isset($_REQUEST[self::request_event_id])) ? (int) $_REQUEST[self::request_event_id] : (int) $_REQUEST[dbEvent::field_id];

  	// persoenliche Anrede...
 		$titles = explode(',', event_cfg_title);
 		$options = '';
 		foreach ($titles as $title) {
 			$selected = (isset($_REQUEST[self::request_title]) && ($_REQUEST[self::request_title] == $title)) ? ' selected="selected"' : '';
 			$options .= sprintf('<option value="%s"%s>%s</option>', $title, $selected, $title); 
 		}
 		$request_select_title = sprintf('<select name="%s">%s</select>', self::request_title, $options);
 		
 		// Eingabefelder erzeugen
 		$input_array = array(
 			self::request_first_name,
 			self::request_last_name,
 			self::request_company,
 			self::request_street,
 			self::request_zip,
 			self::request_city,
 			self::request_email,
 			self::request_phone,
 			self::request_best_time
 		);
 		$input = array();
 		foreach ($input_array as $key) {
 			$input[$key] = sprintf(	'<input type="text" name="%s" value="%s" />',	$key,	(isset($_REQUEST[$key])) ? $_REQUEST[$key] : '');
 		}
 		
 		// Checkboxen erzeugen
 		$checkbox_array = array(
 			self::request_confirm,
 			self::request_terms
 		);
 		$checkbox = '';
 		foreach ($checkbox_array as $key) {
 			$checked = (isset($_REQUEST[$key])) ? ' checked="checked"' : '';
 			$checkbox[$key] = sprintf('<input type="checkbox" name="%s" value="1" %s />', $key, $checked);
 		}
 		
 		// CAPTCHA
 		ob_start();
			call_captcha();
			$call_captcha = ob_get_contents();
		ob_end_clean();
		
 		$data = array(
 			'form_name'								=> 'event_order',
 			'form_action'							=> $this->page_link,
 			'action_name'							=> self::request_action,
 			'action_value'						=> self::action_order_check,
 			'event_name'							=> dbEvent::field_id,
 			'event_value'							=> $event_id,
 			'must_fields_name'				=> self::request_must_fields,
 			
 			'request_response'					=> ($this->isMessage()) ? $this->getMessage() : '',
 			'request_select_title'			=> $request_select_title,
 			'request_input_first_name'	=> $input[self::request_first_name],
 			'request_input_last_name'		=> $input[self::request_last_name],
 			'request_input_company'			=> $input[self::request_company],
 			'request_input_street'			=> $input[self::request_street],
 			'request_input_zip'					=> $input[self::request_zip],
 			'request_input_city'				=> $input[self::request_city],
 			'request_input_email'				=> $input[self::request_email],
 			'request_input_phone'				=> $input[self::request_phone],
 			'request_input_best_time'		=> $input[self::request_best_time],
 			
 			'request_text_message'			=> sprintf('<textarea name="%s">%s</textarea>', self::request_message, (isset($_REQUEST[self::request_message])) ? $_REQUEST[self::request_message] : ''),
 		
 			'request_checkbox_order_confirm'	=> $checkbox[self::request_confirm],
 			'request_checkbox_terms_and_conditions' => $checkbox[self::request_terms],
 		
 			'request_captcha'						=> $call_captcha,
 			'request_submit'						=> sprintf('<input type="submit" value="%s" />', event_btn_ok),
 			'request_abort'							=> sprintf('<input type="button" value="%s" onclick="javascript: window.location = \'%s\'; return false;" />', event_btn_abort, $this->page_link)
 		);
 		
  	if (!$this->getEventData($event_id, $event, $parser_data)) return false;
  	$data = array_merge($data, $parser_data);
  	return $this->getTemplate('frontend.event.order.htt', $data);
  } // orderEvent()
  
 	public function showEvent($show_view=-1) {
 		if (!isset($_REQUEST[self::request_event]) && ($show_view == -1)) {
 			$this->setError(event_error_evt_invalid);
 			return false;
 		}
 		$event_view = (isset($_REQUEST[self::request_event])) ? strtolower(trim($_REQUEST[self::request_event])) : $show_view;
 		$event_view = trim(strtolower($event_view));
 		
 		switch ($event_view):
 		case self::view_id:
 			$result = $this->viewEventID();
 			break;
 		case self::view_day:
 			$result = $this->viewEventDay();
 			break;
 		case self::view_month:
 			$result = $this->viewEventMonth();
 			break;
 		case self::view_week:
 			$result = $this->viewEventWeek();
 			break;
 		case self::view_active:
 			$result = $this->viewEventActive();
 			break;
 		default:
 			// nicht spezifiziertes Event
 			$this->setError(sprintf(event_error_evt_unspecified, $event_view));
 			return false;
 		endswitch;
 		return $result;
 	}
 	
 	public function viewEventID($event_id=-1, $show_details=true) {
 		$show_details = (isset($_REQUEST[self::request_event_detail])) ? (bool) $_REQUEST[self::request_event_detail] : $show_details;
 		$event_id = (isset($_REQUEST[self::request_event_id])) ? (int) $_REQUEST[self::request_event_id] : $event_id;
 		
 		$template = ($show_details) ? 'frontend.event.detail.htt' : 'frontend.event.teaser.htt';
 		if (!$this->getEventData($event_id, $event_data, $parser_data)) return false;
 		if (false == ($event = $this->getTemplate($template, $parser_data))) return false;
 		$data = array('event' => $event);
 		$data = array_merge($data, $parser_data);
 		return $this->getTemplate('frontend.view.id.htt', $data);
 	} // viewEventID()
 	
 	/**
 	 * 
 	 * Enter description here ...
 	 */
 	public function viewEventDay() {
 		global $dbEvent;
 		global $dbEventGroup;
 		
 		if (!isset($_REQUEST[self::request_day]) || !isset($_REQUEST[self::request_month]) || !isset($_REQUEST[self::request_year])) {
 			// keine Parameter gesetzt - aktuelles Datum verwenden!
 			$month = date('n');
 			$day = date('j');
 			$year = date('Y');
 		}
 		else {
	 		$month = (int) $_REQUEST[self::request_month];
	 		$day = (int) $_REQUEST[self::request_day];
	 		$year = (int) $_REQUEST[self::request_year];
 		}
 		
 		$search_date_from = date('Y-m-d H:i:s', mktime(23,59,59,$month,$day-1,$year));
 		$search_date_to = date('Y-m-d H:i:s', mktime(0,0,0,$month,$day+1,$year));
 		$dt = mktime(0,0,0,$month,$day,$year);
 		
 		$weekdays = explode(',', event_cfg_day_names);
 		$months = explode(',', event_cfg_month_names);
		
 		$data = array(
 			'view'											=> 'day',
 			'evt_view_day_date'					=> date(event_cfg_date_str, $dt),
 			'evt_view_day_day'					=> date('j', $dt),
 			'evt_view_day_day_zero'			=> date('d', $dt),
 			'evt_view_day_day_name'			=> trim($weekdays[date('w', $dt)]),
 			'evt_view_day_month_name'		=> trim($months[date('n', $dt)-1]),
 			'evt_view_day_month'				=> date('n', $dt),
 			'evt_view_day_month_zero'		=> date('m', $dt),
 			'evt_view_day_year'					=> date('Y'),
 			'evt_start_link'						=> $this->page_link
 		);
 		
 		$filter_group = '';
 		$group = (isset($_REQUEST[self::param_group]) && !empty($_REQUEST[self::param_group])) ? $_REQUEST[self::param_group] : $this->params[self::param_group];
 		if (!empty($group)) {
 			$where = array(dbEventGroup::field_name => $group);
 			$groups = array();
 			if (!$dbEventGroup->sqlSelectRecord($where, $groups)) {
 				$this->setError($dbEventGroup->getError());
 				return false;
 			}
 			if (count($groups) < 1) {
 				$this->setError(sprintf(event_error_group_invalid, $group));
 				return false;
 			}	
 			$filter_group = sprintf(" AND %s='%s'", dbEvent::field_event_group, $groups[0][dbEventGroup::field_id]);
 		}
 		
 		$SQL = sprintf( "SELECT %s FROM %s WHERE (%s BETWEEN '%s' AND '%s') AND %s='%s'%s",
 										dbEvent::field_id,
 										$dbEvent->getTableName(),
 										dbEvent::field_event_date_from, 
 										$search_date_from,
 										$search_date_to,
 										dbEvent::field_status,
 										dbEvent::status_active,
 										$filter_group);
 		$events = array();
 		if (!$dbEvent->sqlExec($SQL, $events)) {
 			$this->setError($dbEvent->getError());
 			return false;
 		}
 		if (count($events) < 1) {
 			// keine Treffer
 			return $this->getTemplate('frontend.view.none.htt', $data);
 		}
 		$result = '';
 		$show_details = (isset($_REQUEST[self::param_view])) ? (bool) $_REQUEST[self::param_view] : $this->params[self::param_detail];
 		$template = ($show_details) ? 'frontend.event.detail.htt' : 'frontend.event.teaser.htt';
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$result .= $this->getTemplate($template, $parser_data); 
 		}
 		
 		$data['events'] = $result;
 		$data = array_merge($data, $parser_data); 		
 		
 		return $this->getTemplate('frontend.view.day.htt', $data);
 	} // viewEventDay()
  
 	public function viewEventMonth() {
 		global $dbEvent;
 		global $dbEventGroup;
 		
 		if (!isset($_REQUEST[self::request_month]) || !isset($_REQUEST[self::request_year])) {
 			// keine Parameter gesetzt - aktuelles Datum verwenden!
 			$month = date('n');
 			$year = date('Y');
 		}
 		else {
	 		$month = (int) $_REQUEST[self::request_month];
	 		$year = (int) $_REQUEST[self::request_year];
 		}
 		$search_date_from = date('Y-m-d H:i:s', mktime(23,59,59,$month,0,$year));
 		$search_date_to = date('Y-m-d H:i:s', mktime(0,0,0,$month+1,1,$year));
 		$dt = mktime(0,0,0,$month,1,$year);
 		$months = explode(',', event_cfg_month_names);
		
 		if ($month == 1) {
 			$prev_month = 11;
 			$prev_year = $year-1; 
 		}
 		else {
 			$prev_month = $month-2;
 			$prev_year = $year;
 		}
 		if ($month == 12) {
 			$next_month = 0;
 			$next_year = $year+1;
 		}
 		else {
 			$next_month = $month;
 			$next_year = $year;
 		}
 		
 		$data = array(
 			'view'															=> 'month',
 			'evt_start_link'										=> $this->page_link,
 			'evt_view_month_month'							=> $month,
 			'evt_view_month_month_zero'					=> date('m', $dt),
 			'evt_view_month_month_name'					=> $months[$month-1],
 			'evt_view_month_year'								=> $year,
 			'evt_view_month_prev_month_name'		=> $months[$prev_month],
 			'evt_view_month_next_month_name'		=> $months[$next_month],
 			'evt_view_month_prev_month_link'		=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s', 
 																											$this->page_link,
 																											self::request_action,
 																											self::action_event,
 																											self::request_event,
 																											self::view_month,
 																											self::request_month,
 																											$prev_month+1,
 																											self::request_year,
 																											$prev_year),
 			 'evt_view_month_next_month_link'		=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s', 
 																											$this->page_link,
 																											self::request_action,
 																											self::action_event,
 																											self::request_event,
 																											self::view_month,
 																											self::request_month,
 																											$next_month+1,
 																											self::request_year,
 																											$next_year)																		
 		);
 		
 		$filter_group = '';
 		$group = (isset($_REQUEST[self::param_group]) && !empty($_REQUEST[self::param_group])) ? $_REQUEST[self::param_group] : $this->params[self::param_group];
 		if (!empty($group)) {
 			$where = array(dbEventGroup::field_name => $group);
 			$groups = array();
 			if (!$dbEventGroup->sqlSelectRecord($where, $groups)) {
 				$this->setError($dbEventGroup->getError());
 				return false;
 			}
 			if (count($groups) < 1) {
 				$this->setError(sprintf(event_error_group_invalid, $group));
 				return false;
 			}	
 			$filter_group = sprintf(" AND %s='%s'", dbEvent::field_event_group, $groups[0][dbEventGroup::field_id]);
 		}
 		
 		$SQL = sprintf( "SELECT %s FROM %s WHERE (%s BETWEEN '%s' AND '%s') AND %s='%s' %sORDER BY %s ASC",
 										dbEvent::field_id,
 										$dbEvent->getTableName(),
 										dbEvent::field_event_date_from, 
 										$search_date_from,
 										$search_date_to,
 										dbEvent::field_status,
 										dbEvent::status_active,
 										$filter_group,
 										dbEvent::field_event_date_from);
 		$events = array();
 		if (!$dbEvent->sqlExec($SQL, $events)) {
 			$this->setError($dbEvent->getError());
 			return false;
 		}
 		if (count($events) < 1) {
 			return $this->getTemplate('frontend.view.none.htt', $data);
 		}
 		
 		$result = '';
 		$show_details = (isset($_REQUEST[self::param_view])) ? (bool) $_REQUEST[self::param_view] : $this->params[self::param_detail];
 		$template = ($show_details) ? 'frontend.event.detail.htt' : 'frontend.event.teaser.htt';
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$result .= $this->getTemplate($template, $parser_data); 
 		}
 		
 		$data['events'] = $result;
 		$data = array_merge($data, $parser_data);
		return $this->getTemplate('frontend.view.month.htt', $data); 		
 	} // viewEventMonth()
 	
 	public function viewEventWeek() {
 		global $dbEvent;
 		global $dbEventGroup;
 		
 		if (!isset($_REQUEST[self::request_day]) || !isset($_REQUEST[self::request_month]) || !isset($_REQUEST[self::request_year])) {
 			// keine Parameter gesetzt - aktuelles Datum verwenden!
 			$month = date('n');
 			$day = date('j');
 			$year = date('Y');
 		}
 		else {
	 		$month = (int) $_REQUEST[self::request_month];
	 		$day = (int) $_REQUEST[self::request_day];
	 		$year = (int) $_REQUEST[self::request_year];
 		}
 		$start = $this->getMondayOfWeekDate(mktime(0,0,0,$month,$day,$year));
 		$monday = date('j', $start);
 		$day = date('j', $start);
 		$month = date('n', $start);
 		$year = date('Y', $start);
 		
 		$search_date_from = date('Y-m-d H:i:s', mktime(23,59,59,$month,$monday-1,$year));
 		$search_date_to = date('Y-m-d H:i:s', mktime(0,0,0,$month,$monday+7,$year));
 		$dt = mktime(0,0,0,$month,$monday,$year);
 		$months = explode(',', event_cfg_month_names);
		
 		$prev_date = mktime(0,0,0,$month,$monday-7,$year);
 		$next_date = mktime(0,0,0,$month,$monday+7,$year);
 		
 		$data = array(
 			'view'													=> 'week',
 			'evt_start_link'								=> $this->page_link,
 			'evt_view_week_monday'					=> date('j', $dt),
 			'evt_view_week_monday_zero'			=> date('d', $dt),
 			'evt_view_week_sunday'					=> date('j', mktime(0,0,0,$month,$monday+6,$year)),
 			'evt_view_week_sunday_zero'			=> date('d', mktime(0,0,0,$month,$monday+6,$year)),
 			'evt_view_week_week'						=> (int) date('W', $dt),
 			'evt_view_week_week_zero'				=> date('W', $dt),
 			'evt_view_week_year'						=> date('Y', $dt),
 			'evt_view_week_month'						=> date('n', $dt),
 			'evt_view_week_month_zero'			=> date('m', $dt),
 			'evt_view_week_month_name'			=> $months[date('n')-1],
 			'evt_view_week_prev_week_link'	=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s&%s=%s',
 																									$this->page_link,
 																									self::request_action,
 																									self::action_event,
 																									self::request_event,
 																									self::view_week,
 																									self::request_month,
 																									date('n', $prev_date),
 																									self::request_day,
 																									date('j', $prev_date),
 																									self::request_year,
 																									date('Y', $prev_date)),
 			'evt_view_week_next_week_link'	=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s&%s=%s',
 																									$this->page_link,
 																									self::request_action,
 																									self::action_event,
 																									self::request_event,
 																									self::view_week,
 																									self::request_month,
 																									date('n', $next_date),
 																									self::request_day,
 																									date('j', $next_date),
 																									self::request_year,
 																									date('Y', $next_date))
 																									
 		);
 		
 		$filter_group = '';
 		$group = (isset($_REQUEST[self::param_group]) && !empty($_REQUEST[self::param_group])) ? $_REQUEST[self::param_group] : $this->params[self::param_group];
 		if (!empty($group)) {
 			$where = array(dbEventGroup::field_name => $group);
 			$groups = array();
 			if (!$dbEventGroup->sqlSelectRecord($where, $groups)) {
 				$this->setError($dbEventGroup->getError());
 				return false;
 			}
 			if (count($groups) < 1) {
 				$this->setError(sprintf(event_error_group_invalid, $group));
 				return false;
 			}	
 			$filter_group = sprintf(" AND %s='%s'", dbEvent::field_event_group, $groups[0][dbEventGroup::field_id]);
 		}
 		
 		$SQL = sprintf( "SELECT %s FROM %s WHERE (%s BETWEEN '%s' AND '%s') AND %s='%s' %sORDER BY %s ASC",
 										dbEvent::field_id,
 										$dbEvent->getTableName(),
 										dbEvent::field_event_date_from, 
 										$search_date_from,
 										$search_date_to,
 										dbEvent::field_status,
 										dbEvent::status_active,
 										$filter_group,
 										dbEvent::field_event_date_from);
 		$events = array();
 		if (!$dbEvent->sqlExec($SQL, $events)) {
 			$this->setError($dbEvent->getError());
 			return false;
 		}
 		if (count($events) < 1) {
 			return $this->getTemplate('frontend.view.none.htt', $data);
 		}
 		
 		$result = '';
 		$show_details = (isset($_REQUEST[self::param_view])) ? (bool) $_REQUEST[self::param_view] : $this->params[self::param_detail];
 		$template = ($show_details) ? 'frontend.event.detail.htt' : 'frontend.event.teaser.htt';
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$result .= $this->getTemplate($template, $parser_data); 
 		}
 		
 		$data['events'] = $result;
 		$data = array_merge($data, $parser_data);
 		return $this->getTemplate('frontend.view.week.htt', $data);
 	} // viewEventWeek()
 	
 	public function viewEventActive() {
 		global $dbEvent;
 		global $dbEventGroup;
 		 		
 		$search_date_from = date('Y-m-d H:i:s', mktime(23,59,59,date('n'),date('j')-1,date('Y')));
 		$search_date_to = date('Y-m-d H:i:s', mktime(23,59,59,date('n'),date('j'),date('Y')));
 		$months = explode(',', event_cfg_month_names);
		
 		$filter_group = '';
 		$group = (isset($_REQUEST[self::param_group]) && !empty($_REQUEST[self::param_group])) ? $_REQUEST[self::param_group] : $this->params[self::param_group];
 		if (!empty($group)) {
 			$where = array(dbEventGroup::field_name => $group);
 			$groups = array();
 			if (!$dbEventGroup->sqlSelectRecord($where, $groups)) {
 				$this->setError($dbEventGroup->getError());
 				return false;
 			}
 			if (count($groups) < 1) {
 				$this->setError(sprintf(event_error_group_invalid, $group));
 				return false;
 			}	
 			$filter_group = sprintf(" AND %s='%s'", dbEvent::field_event_group, $groups[0][dbEventGroup::field_id]);
 		}
 		$SQL = sprintf( "SELECT %s FROM %s WHERE (%s <= '%s' AND %s >= '%s') AND %s='%s' %sORDER BY %s ASC",
 										dbEvent::field_id,
 										$dbEvent->getTableName(),
 										dbEvent::field_publish_date_from,  
 										$search_date_from,
 										dbEvent::field_publish_date_to,
 										$search_date_to,
 										dbEvent::field_status,
 										dbEvent::status_active,
 										$filter_group,
 										dbEvent::field_event_date_from);
 		$events = array();
 		if (!$dbEvent->sqlExec($SQL, $events)) {
 			$this->setError($dbEvent->getError());
 			return false;
 		}
 		if (count($events) < 1) {
 			$this->setMessage(sprintf(event_msg_no_event_at_date, $months[$month-1]));
 			return $this->getMessage();
 		}
 		
 		$result = '';
 		$show_details = (isset($_REQUEST[self::param_view])) ? (bool) $_REQUEST[self::param_view] : $this->params[self::param_detail];
 		$template = ($show_details) ? 'frontend.event.detail.htt' : 'frontend.event.teaser.htt';
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$result .= $this->getTemplate($template, $parser_data); 
 		}
 		
 		$data = array(
 			'events'			=> $result
 		);
 		$data = array_merge($data, $parser_data);
 		return $this->getTemplate('frontend.view.active.htt', $data);
 	} // viewEventActive
 	
} // class eventFrontend

?>