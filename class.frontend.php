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

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');
require_once(WB_PATH.'/include/captcha/captcha.php');
require_once(WB_PATH.'/framework/class.wb.php');
require_once(WB_PATH.'/modules/droplets_extension/interface.php');

class eventFrontend {
	const REQUEST_ACTION				= 'kea';
	const REQUEST_EVENT					= 'evt';
	const REQUEST_YEAR					= 'y';
	const REQUEST_MONTH					= 'm';
	const REQUEST_DAY						= 'd';
	const REQUEST_EVENT_ID			= 'id';
	const REQUEST_EVENT_DETAIL	= 'det';
	const REQUEST_FREE_FIELDS		= 'ff';
	const REQUEST_PERMA_LINK		= 'perl';

	const REQUEST_MUST_FIELDS	= 'mf';

	const REQUEST_TITLE				= 'title';
	const REQUEST_FIRST_NAME	= 'fn';
	const REQUEST_LAST_NAME		= 'ln';
	const REQUEST_COMPANY			= 'com';
	const REQUEST_STREET			= 'str';
	const REQUEST_ZIP					= 'zip';
	const REQUEST_CITY				= 'cty';
	const REQUEST_EMAIL				= 'eml';
	const REQUEST_PHONE				= 'phn';
	const REQUEST_BEST_TIME		= 'bt';
	const REQUEST_CONFIRM			= 'con';
	const REQUEST_TERMS				= 'trm';
	const REQUEST_PRIVACY			= 'prv';
	const REQUEST_MESSAGE			= 'msg';
	const REQUEST_CAPTCHA			= 'cpt';

	const ACTION_DEFAULT 			= 'def';
	const ACTION_DAY					= 'day';
	const ACTION_EVENT				= 'evt';
	const ACTION_ORDER				= 'ord';
	const ACTION_ORDER_CHECK	= 'chk';

	const PARAM_VIEW					= 'view';
	const PARAM_PRESET				= 'preset';
	const PARAM_DETAIL				= 'detail';
	const PARAM_GROUP					= 'group';
	const PARAM_EVENT_ID			= 'event_id';
	const PARAM_IGNORE_TOPICS	= 'ignore_topics';
	const PARAM_RESPONSE_ID		= 'response_id'; // noch inaktiv!!!
	const PARAM_SEARCH				= 'search';
	const PARAM_HEADER				= 'header';
	const PARAM_CSS						= 'css';

	const VIEW_ID							= 'id';
	const VIEW_DAY						= 'day';
	const VIEW_WEEK						= 'week';
	const VIEW_MONTH					= 'month';
	const VIEW_ACTIVE					= 'active';

	private $params = array(
		self::PARAM_VIEW				=> self::VIEW_ACTIVE,
		self::PARAM_PRESET			=> 1,
		self::PARAM_DETAIL			=> false,
		self::PARAM_GROUP				=> '',
		self::PARAM_EVENT_ID		=> -1,
		self::PARAM_RESPONSE_ID => -1,
		self::PARAM_IGNORE_TOPICS => false,
		self::PARAM_SEARCH			=> false,
		self::PARAM_HEADER			=> false,
		self::PARAM_CSS					=> true
	);

	private $template_path;
	private $page_link;

	public function __construct() {
		global $kitLibrary;
		$url = '';
		$_SESSION['FRONTEND'] = true;
		$kitLibrary->getPageLinkByPageID(PAGE_ID, $url);
		$this->page_link = $url;
		$this->template_path = WB_PATH.'/modules/kit_event/htt/'.$this->params[self::PARAM_PRESET].'/'.KIT_EVT_LANGUAGE.'/';
		date_default_timezone_set(event_cfg_time_zone);
	} // __construct();

	public function getParams() {
		return $this->params;
	} // getParams()

	public function setParams($params = array()) {
		$this->params = $params;
		$this->template_path = WB_PATH.'/modules/kit_event/htt/'.$this->params[self::PARAM_PRESET].'/'.KIT_EVT_LANGUAGE.'/';
		if (!file_exists($this->template_path)) {
			$this->setError(sprintf(event_error_preset_not_exists, '/modules/kit_event/htt/'.$this->params[self::PARAM_PRESET].'/'.KIT_EVT_LANGUAGE.'/'));
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
  	if ((isset($_REQUEST[self::REQUEST_PERMA_LINK]) && is_numeric($_REQUEST[self::REQUEST_PERMA_LINK])) || ($this->params[self::PARAM_EVENT_ID] !== -1)) {
  		$_REQUEST[self::REQUEST_ACTION] = self::ACTION_EVENT;
  		$_REQUEST[self::REQUEST_EVENT] = self::VIEW_ID;
  		$_REQUEST[self::REQUEST_EVENT_ID] = (isset($_REQUEST[self::REQUEST_PERMA_LINK]) && is_numeric($_REQUEST[self::REQUEST_PERMA_LINK])) ? $_REQUEST[self::REQUEST_PERMA_LINK] : $this->params[self::PARAM_EVENT_ID];
  		$_REQUEST[self::REQUEST_EVENT_DETAIL] = (isset($_REQUEST[self::REQUEST_EVENT_DETAIL])) ? $_REQUEST[self::REQUEST_EVENT_DETAIL] : $this->params[self::PARAM_DETAIL];
  	}
    isset($_REQUEST[self::REQUEST_ACTION]) ? $action = $_REQUEST[self::REQUEST_ACTION] : $action = self::ACTION_DEFAULT;
    if (isset($_REQUEST[self::REQUEST_EVENT])) $action = self::ACTION_EVENT;
  	switch ($action):
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

  	if ($this->isError()) $result = $this->getError();
		return $result;
  } // action

  public function getMondayOfWeekDate($date) {
  	$dow = date('w', $date);
  	if ($dow == 0) $dow = 7;
  	$sub = $dow-1;
  	return mktime(0,0,0,date('n', $date),date('j', $date)-$sub,date('Y',$date));
  }

  private function getStartEndDates($event_data=array(), $is_start=true) {
  	$date = ($is_start) ? strtotime($event_data[dbEvent::field_event_date_from]) : strtotime($event_data[dbEvent::field_event_date_to]);
  	$publish = ($is_start) ? strtotime($event_data[dbEvent::field_publish_date_from]) : strtotime($event_data[dbEvent::field_publish_date_to]);

  	$weekdays = explode(',', event_cfg_day_names);
 		$months = explode(',', event_cfg_month_names);

  	$dates = array(
 			'timestamp'					=> $date,
 			'date'							=> date(event_cfg_date_str, $date),
 			'datetime'					=> date(event_cfg_datetime_str, $date),
 			'time'							=> date(event_cfg_time_str, $date),
 			'day'								=> date('j', $date),
 			'day_zero'					=> date('d', $date),
 			'day_name'					=> trim($weekdays[date('w', $date)]),
 			'day_name_2'				=> substr(trim($weekdays[date('w', $date)]), 0, 2),
 			'month'							=> date('n', $date),
 			'month_zero'				=> date('m', $date),
 			'month_name'				=> trim($months[date('n', $date)-1]),
 			'month_name_3'			=> substr(trim($months[date('n', $date)-1]), 0, 3),
 			'year'							=> date('Y', $date),
 			'year_2'						=> date('y', $date),
 			'week'							=> date('W', $date),
			'publish_date'			=> date(event_cfg_date_str, $publish),
			'publish_timestamp'	=> $publish,
  	  'iso'               => date('c', $date)
 		);
 		return $dates;
  } // getStartEndDates()

  /**
   * Daten fuer die angegebene Event ID auslesen und zusaetzlich ein Array mit Informationen
   * fuer die Ausgabe ueber beliebige Templates erzeugen
   * @param INT $event_id
   * @param REFERENCE ARRAY $event_data
   * @param REFERENCE ARRAY $parser_data
   * @return BOOL true on success
   */
  public function getEventData($event_id, &$event_data=array(), &$event_parser=array()) {
  	global $dbEvent;
  	global $dbEventItem;
  	global $dbEventGroup;
  	global $dbEventCfg;
  	global $kitLibrary;

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

 		/*
 		if ($event_data[dbEventItem::field_costs] > 0) {
 			$costs = sprintf(event_cfg_currency, number_format($event_data[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator));
 		}
 		else {
 			$costs = event_text_none;
 		}
 		*/

 		$start = strtotime($event_data[dbEvent::field_event_date_from]);
 		$end = strtotime($event_data[dbEvent::field_event_date_to]);

 		// QR Code
 		if ($dbEventCfg->getValue(dbEventCfg::cfgQRCodeExec) == 1) {
 			// QR Code verwenden
 			$dir = $kitLibrary->removeLeadingSlash($dbEventCfg->getValue(dbEventCfg::cfgQRCodeDir));
  		$dir = $kitLibrary->addSlash($dir);
  		$dir_path = WB_PATH.MEDIA_DIRECTORY.'/'.$dir;
  		$filename = $event_data[dbEvent::field_qrcode_image];
  		if (!empty($filename) && file_exists($dir_path.$filename)) {
	  		list($qrcode_width, $qrcode_height) = getimagesize($dir_path.$filename);
	  		$qrcode_src = WB_URL.MEDIA_DIRECTORY.'/'.$dir.$filename;
	  		$qrcode_type = $dbEventCfg->getValue(dbEventCfg::cfgQRCodeContent);
	  		$qrcode_text = ($qrcode_type == 1) ? event_text_qrcode_content_url : event_text_qrcode_content_ical;
  		}
  		else {
  			$qrcode_src = '';
	 			$qrcode_width = 0;
	 			$qrcode_height = 0;
	 			$qrcode_text = '';
	 			$qrcode_type = 0;
  		}
 		}
 		else {
 			$qrcode_src = '';
 			$qrcode_width = 0;
 			$qrcode_height = 0;
 			$qrcode_text = '';
 			$qrcode_type = 0;
 		}
 		// iCal
 		if (($dbEventCfg->getValue(dbEventCfg::cfgICalExec) == 1) && !empty($event_data[dbEvent::field_ical_file])) {
 			$dir = $kitLibrary->removeLeadingSlash($dbEventCfg->getValue(dbEventCfg::cfgICalDir));
  		$dir = $kitLibrary->addSlash($dir);
  		$dir_url = WB_URL.MEDIA_DIRECTORY.'/'.$dir;
  		$filename = $event_data[dbEvent::field_ical_file];
  		$ical_link = $dir_url.$filename;
 		}
 		else {
 			$ical_link = '';
 		}


 		$event_parser = array(
 			//'group_name'							=> $group_name,
 			//'group_desc'							=> $group_desc,
 			//'participants_max'				=> $participants_max,
 			//'participants_total'			=> $event_data[dbEvent::field_participants_total],
 			//'participants_free'				=> $participants_free,
			//'deadline_date'						=> date(event_cfg_date_str, strtotime($event_data[dbEvent::field_deadline])),
 			//'deadline_timestamp'			=> strtotime($event_data[dbEvent::field_deadline]),
 		 	//'desc_short'							=> stripslashes($event_data[dbEventItem::field_desc_short]),
 			//'desc_long'								=> stripslashes($event_data[dbEventItem::field_desc_long]),
 			//'costs'										=> number_format($costs, 2, event_cfg_decimal_separator, event_cfg_thousand_separator),
 			//'link_desc'								=> stripslashes($event_data[dbEventItem::field_desc_link]),
 			//'link_order'							=> sprintf('%s?%s=%s&%s=%s', $this->page_link, self::REQUEST_ACTION, self::ACTION_ORDER, self::REQUEST_EVENT_ID, $event_id),
  		//'link_detail'							=> sprintf('%s?%s=%s&%s=%s&%s=%s&%s=%s', $this->page_link, self::REQUEST_ACTION, self::ACTION_EVENT, self::REQUEST_EVENT_ID, $event_id, self::REQUEST_EVENT, self::VIEW_ID, self::REQUEST_EVENT_DETAIL, 1),
  		//'link_start'							=> $this->page_link,

 			'headline'								=> $event_data[dbEventItem::field_title],
 			'id'											=> $event_data[dbEvent::field_id],
 			'group'										=> array(
 																			'name'				=> $group_name,
 																			'description'	=> $group_desc
 																		),
 			'start'										=> $this->getStartEndDates($event_data, true),
 			'end'											=> $this->getStartEndDates($event_data, false),
 			'participants'						=> array(
 																			'max'		=> $participants_max,
 																			'total'	=> $event_data[dbEvent::field_participants_total],
 																			'free'	=> $participants_free
 																		),
 			'deadline'								=> array(
 																			'date'	=> strtotime($event_data[dbEvent::field_deadline])
 																		),
 			'description'							=> array(
 																			'short'	=> stripslashes($event_data[dbEventItem::field_desc_short]),
 																			'long'	=> stripslashes($event_data[dbEventItem::field_desc_long])
 																		),
 			'location'								=> $event_data[dbEventItem::field_location],
 			'costs'										=> array(
 																			'value'		=> $event_data[dbEventItem::field_costs],
 																			'format'	=> array(
 																											'float'		=> number_format($event_data[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator),
 																											'currency'=> sprintf(event_cfg_currency, number_format($event_data[dbEventItem::field_costs], 2, event_cfg_decimal_separator, event_cfg_thousand_separator))
 																										),
 																		),
 			'link'										=> array(
 																			'description'		=> stripslashes($event_data[dbEventItem::field_desc_link]),
 																			'register'			=> sprintf(	'%s%s%s',
 																		 															$this->page_link,
 																		 															(strpos($this->page_link, '?') === false) ? '?' : '&',
 																		 															http_build_query(array(	self::REQUEST_ACTION 		=> self::ACTION_ORDER,
 																		 																											self::REQUEST_EVENT_ID 	=> $event_id))),
 																		 	'detail'				=> sprintf(	'%s%s%s',
 																		 															$this->page_link,
 																		 															(strpos($this->page_link, '?') === false) ? '?' : '&',
 																		 															http_build_query(array(	self::REQUEST_ACTION 				=> self::ACTION_EVENT,
 																		 																											self::REQUEST_EVENT_ID 			=> $event_id,
 																		 																											self::REQUEST_EVENT 				=> self::VIEW_ID,
 																		 																											self::REQUEST_EVENT_DETAIL 	=> 1 ))),
 																			'start'					=> $this->page_link,
 																		 	'permanent'			=> (empty($event_data[dbEvent::field_perma_link])) ? '' : WB_URL.PAGES_DIRECTORY.'/'.$event_data[dbEvent::field_perma_link],
 																		 	'ical'					=> $ical_link
 																		),
 			'qr_code'									=> array(
 																			'is_active'		=> (int) $dbEventCfg->getValue(dbEventCfg::cfgQRCodeExec),
 																			'image'				=> array(
 																													'src'		=> $qrcode_src,
 																													'width'	=> $qrcode_width,
 																													'height'=> $qrcode_height,
 																													'text'	=> $qrcode_text,
 																													'type'	=> $qrcode_type
 																												)
 																		),
 		);
 		return true;
  } // getEventData()

  /**
   * Anmeldung zu einem Event pruefen, ggf. wieder Anmeldedialog mit Hinweisen
   * anzeigen. Wenn OK, Daten uebernehmen, Zaehler aktualisieren und E-Mails
   * an Besteller sowie an den Seitenbetreiber versenden
   * @return FALSE on error or DIALOG/MESSAGE on success
   */
  public function checkOrder() {
  	global $kitLibrary;
  	global $dbEvent;
  	global $dbEventOrder;
  	global $wb;

  	if (!isset($_REQUEST[self::REQUEST_EVENT_ID]) && !isset($_REQUEST[dbEvent::field_id])) {
  		$this->setError(event_error_evt_invalid);
  		return false;
  	}
  	$event_id = (isset($_REQUEST[self::REQUEST_EVENT_ID])) ? (int) $_REQUEST[self::REQUEST_EVENT_ID] : (int) $_REQUEST[dbEvent::field_id];

  	if (!isset($_REQUEST[self::REQUEST_MUST_FIELDS])) {
  		$this->setError(event_error_must_fields_missing);
  		return false;
  	}
  	$mf = strtolower($_REQUEST[self::REQUEST_MUST_FIELDS]);
  	$mf = str_replace(' ', '', $mf);
  	$must_fields = explode(',', $mf);
 		if (!in_array(self::REQUEST_EMAIL, $must_fields)) $must_fields[] = self::REQUEST_EMAIL;
  	$message = '';
  	foreach ($must_fields as $must_field) {
  		switch ($must_field):
  		case self::REQUEST_CAPTCHA:
		  	if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) $message .= event_msg_captcha_invalid;
				break;
  		case self::REQUEST_CITY:
  			if (!isset($_REQUEST[self::REQUEST_CITY]) || (strlen($_REQUEST[self::REQUEST_CITY]) < 4)) $message .= event_msg_must_city;
  			break;
  		case self::REQUEST_EMAIL:
		  	if (!isset($_REQUEST[self::REQUEST_EMAIL]) || !$kitLibrary->validateEMail($_REQUEST[self::REQUEST_EMAIL])) {
					$message .= sprintf(event_msg_invalid_email, $_REQUEST[self::REQUEST_EMAIL]);
				}
  			break;
  		case self::REQUEST_FIRST_NAME:
  			if (!isset($_REQUEST[self::REQUEST_FIRST_NAME]) || empty($_REQUEST[self::REQUEST_FIRST_NAME])) $message .= event_msg_must_first_name;
  			break;
  		case self::REQUEST_LAST_NAME:
  			if (!isset($_REQUEST[self::REQUEST_LAST_NAME]) || empty($_REQUEST[self::REQUEST_LAST_NAME])) $message .= event_msg_must_last_name;
  			break;
			case self::REQUEST_PHONE:
  			if (!isset($_REQUEST[self::REQUEST_PHONE]) || empty($_REQUEST[self::REQUEST_PHONE])) $message .= event_msg_must_phone;
  			break;
  		case self::REQUEST_STREET:
  			if (!isset($_REQUEST[self::REQUEST_STREET]) || empty($_REQUEST[self::REQUEST_STREET])) $message .= event_msg_must_street;
  			break;
  		case self::REQUEST_TERMS:
  			if (!isset($_REQUEST[self::REQUEST_TERMS])) $message .= event_msg_must_terms_and_conditions;
  			break;
  		case self::REQUEST_PRIVACY:
  			if (!isset($_REQUEST[self::REQUEST_PRIVACY])) $message .= event_msg_must_data_privacy;
  			break;
  		case self::REQUEST_ZIP:
  			if (!isset($_REQUEST[self::REQUEST_ZIP]) || empty($_REQUEST[self::REQUEST_ZIP])) $message .= event_msg_must_zip;
  			break;
  		endswitch;
  	}
  	if (!empty($message)) {
  		$this->setMessage($message);
  		return $this->orderEvent();
  	}

		// ok - Daten sichern und Bestaetigungsmails versenden
		$free_fields = (isset($_REQUEST[self::REQUEST_FREE_FIELDS])) ? explode(',', $_REQUEST[self::REQUEST_FREE_FIELDS]) : array();

  	$orderData = array(
  		dbEventOrder::field_best_time			=> (isset($_REQUEST[self::REQUEST_BEST_TIME])) ? $_REQUEST[self::REQUEST_BEST_TIME] : '',
  		dbEventOrder::field_city					=> (isset($_REQUEST[self::REQUEST_CITY])) ? $_REQUEST[self::REQUEST_CITY] : '',
  		dbEventOrder::field_company				=> (isset($_REQUEST[self::REQUEST_COMPANY])) ? $_REQUEST[self::REQUEST_COMPANY] : '',
  		dbEventOrder::field_confirm_order	=> (isset($_REQUEST[self::REQUEST_CONFIRM])) ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00',
  		dbEventOrder::field_email					=> strtolower($_REQUEST[self::REQUEST_EMAIL]),
  		dbEventOrder::field_event_id			=> $event_id,
  		dbEventOrder::field_first_name		=> (isset($_REQUEST[self::REQUEST_FIRST_NAME])) ? $_REQUEST[self::REQUEST_FIRST_NAME] : '',
  		dbEventOrder::field_last_name			=> (isset($_REQUEST[self::REQUEST_LAST_NAME])) ? $_REQUEST[self::REQUEST_LAST_NAME] : '',
  		dbEventOrder::field_message				=> (isset($_REQUEST[self::REQUEST_MESSAGE])) ? $_REQUEST[self::REQUEST_MESSAGE] : '',
  		dbEventOrder::field_order_date		=> date('Y-m-d H:i:s'),
  		dbEventOrder::field_phone					=> (isset($_REQUEST[self::REQUEST_PHONE])) ? $_REQUEST[self::REQUEST_PHONE] : '',
  		dbEventOrder::field_street				=> (isset($_REQUEST[self::REQUEST_STREET])) ? $_REQUEST[self::REQUEST_STREET] : '',
  		dbEventOrder::field_title					=> (isset($_REQUEST[self::REQUEST_TITLE])) ? $_REQUEST[self::REQUEST_TITLE] : '',
  		dbEventOrder::field_zip						=> (isset($_REQUEST[self::REQUEST_ZIP])) ? $_REQUEST[self::REQUEST_ZIP] : '',
  		dbEventOrder::field_free_1				=> (isset($_REQUEST[dbEventOrder::field_free_1])) ? (isset($free_fields[0])) ? $free_fields[0].'|'.$_REQUEST[dbEventOrder::field_free_1] : '|'.$_REQUEST[dbEventOrder::field_free_1] : '',
  		dbEventOrder::field_free_2				=> (isset($_REQUEST[dbEventOrder::field_free_2])) ? (isset($free_fields[1])) ? $free_fields[1].'|'.$_REQUEST[dbEventOrder::field_free_2] : '|'.$_REQUEST[dbEventOrder::field_free_2] : '',
  		dbEventOrder::field_free_3				=> (isset($_REQUEST[dbEventOrder::field_free_3])) ? (isset($free_fields[2])) ? $free_fields[2].'|'.$_REQUEST[dbEventOrder::field_free_3] : '|'.$_REQUEST[dbEventOrder::field_free_3] : '',
  		dbEventOrder::field_free_4				=> (isset($_REQUEST[dbEventOrder::field_free_4])) ? (isset($free_fields[3])) ? $free_fields[3].'|'.$_REQUEST[dbEventOrder::field_free_4] : '|'.$_REQUEST[dbEventOrder::field_free_4] : '',
  		dbEventOrder::field_free_5				=> (isset($_REQUEST[dbEventOrder::field_free_5])) ? (isset($free_fields[4])) ? $free_fields[4].'|'.$_REQUEST[dbEventOrder::field_free_5] : '|'.$_REQUEST[dbEventOrder::field_free_5] : ''
  	);

  	$order_id = -1;
  	if (!$dbEventOrder->sqlInsertRecord($orderData, $order_id))  {
  		$this->setError($dbEventOrder->getError());
  		return false;
  	}

  	// wenn eine Anmeldung erfolgt ist, muss der Zaehler bei dbEvent erhoeht werden!
  	if (false !== ($dt = $orderData[dbEventOrder::field_confirm_order])) {
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
  	$order = array(
  		'title'							=> $orderData[dbEventOrder::field_title],
  		'first_name'				=> $orderData[dbEventOrder::field_first_name],
  		'last_name'					=> $orderData[dbEventOrder::field_last_name],
  		'company'						=> $orderData[dbEventOrder::field_company],
  		'street'						=> $orderData[dbEventOrder::field_street],
  		'zip'								=> $orderData[dbEventOrder::field_zip],
  		'city'							=> $orderData[dbEventOrder::field_city],
  		'email'							=> $orderData[dbEventOrder::field_email],
  		'phone'							=> $orderData[dbEventOrder::field_phone],
  		'best_time'					=> $orderData[dbEventOrder::field_best_time],
  		'message'						=> $orderData[dbEventOrder::field_message],
  		'confirm_datetime'	=> (!strtotime($orderData[dbEventOrder::field_confirm_order])) ? NULL : date(event_cfg_datetime_str, strtotime($orderData[dbEventOrder::field_confirm_order])),
  		'confirm_timestamp' => (!strtotime($orderData[dbEventOrder::field_confirm_order])) ? NULL : strtotime($orderData[dbEventOrder::field_confirm_order]),
  		'free_1'						=> substr($orderData[dbEventOrder::field_free_1], strpos($orderData[dbEventOrder::field_free_1], '|')+1),
  		'free_2'						=> substr($orderData[dbEventOrder::field_free_2], strpos($orderData[dbEventOrder::field_free_2], '|')+1),
  		'free_3'						=> substr($orderData[dbEventOrder::field_free_3], strpos($orderData[dbEventOrder::field_free_3], '|')+1),
  		'free_4'						=> substr($orderData[dbEventOrder::field_free_4], strpos($orderData[dbEventOrder::field_free_4], '|')+1),
  		'free_5'						=> substr($orderData[dbEventOrder::field_free_5], strpos($orderData[dbEventOrder::field_free_5], '|')+1)
 		);

 		if (!$this->getEventData($event_id, $event, $event_parser)) return false;
 		$data = array(
 			'order'		=> $order,
 			'event'		=> $event_parser
 		);

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

  	if (!isset($_REQUEST[self::REQUEST_EVENT_ID]) && !isset($_REQUEST[dbEvent::field_id])) {
  		$this->setError(event_error_evt_invalid);
  		return false;
  	}
  	$event_id = (isset($_REQUEST[self::REQUEST_EVENT_ID])) ? (int) $_REQUEST[self::REQUEST_EVENT_ID] : (int) $_REQUEST[dbEvent::field_id];

  	if (!$this->getEventData($event_id, $event, $parser_data)) return false;

  	$event_group = $event[dbEvent::field_event_group];

  	$request = array();
  	// persoenliche Anrede...
 		$titles = explode(',', event_cfg_title);
 		$options = '';
 		$title_values = array();
 		foreach ($titles as $title) {
 			$title_values[] = array(
 				'value'			=> $title,
 				'text'			=> $title,
 				'selected'	=> (isset($_REQUEST[self::REQUEST_TITLE]) && ($_REQUEST[self::REQUEST_TITLE] == $title)) ? 1 : NULL
 			);
 		}
 		$request['title']['name'] = self::REQUEST_TITLE;
 		$request['title']['value'] = $title_values;

 		// Eingabefelder erzeugen
 		$input_array = array(
 			'first_name'		=> self::REQUEST_FIRST_NAME,
 			'last_name'			=> self::REQUEST_LAST_NAME,
 			'company'				=> self::REQUEST_COMPANY,
 			'street'				=> self::REQUEST_STREET,
 			'zip'						=> self::REQUEST_ZIP,
 			'city'					=> self::REQUEST_CITY,
 			'email'					=> self::REQUEST_EMAIL,
 			'phone'					=> self::REQUEST_PHONE,
 			'best_time'			=> self::REQUEST_BEST_TIME,
 			'message'				=> self::REQUEST_MESSAGE,
 			'confirm_order'	=> self::REQUEST_CONFIRM,
 			'confirm_terms'	=> self::REQUEST_TERMS,
 			'confirm_privacy' => self::REQUEST_PRIVACY,
 			'free_1'				=> dbEventOrder::field_free_1,
 			'free_2'				=> dbEventOrder::field_free_2,
 			'free_3'				=> dbEventOrder::field_free_3,
 			'free_4'				=> dbEventOrder::field_free_4,
 			'free_5'				=> dbEventOrder::field_free_5
 		);
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
 			'form_name'								=> 'event_order',
 			'form_action'							=> $this->page_link,
 			'action_name'							=> self::REQUEST_ACTION,
 			'action_value'						=> self::ACTION_ORDER_CHECK,
 			'event_name'							=> dbEvent::field_id,
 			'event_value'							=> $event_id,
 			'must_fields_name'				=> self::REQUEST_MUST_FIELDS,
 			'define_free_fields'			=> self::REQUEST_FREE_FIELDS,
 			'event'										=> $parser_data,
			'response'								=> ($this->isMessage()) ? $this->getMessage() : NULL,
 			'request'									=> $request,
 		);

  	return $this->getTemplate('frontend.event.order.htt', $data);
  } // orderEvent()

 	public function showEvent($show_view=-1) {
 		if (!isset($_REQUEST[self::REQUEST_EVENT]) && ($show_view == -1)) {
 			$this->setError(event_error_evt_invalid);
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
	 		register_droplet_css('kit_event', PAGE_ID, 'kit_event', 'frontend.css');
 		}

 		switch ($event_view):
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
 		default:
 			// nicht spezifiziertes Event
 			$this->setError(sprintf(event_error_evt_unspecified, $event_view));
 			return false;
 		endswitch;
 		return $result;
 	}

 	public function viewEventID($event_id=-1, $show_details=true) {
 		$show_details = (isset($_REQUEST[self::REQUEST_EVENT_DETAIL])) ? (bool) $_REQUEST[self::REQUEST_EVENT_DETAIL] : $show_details;
 		$event_id = (isset($_REQUEST[self::REQUEST_EVENT_ID])) ? (int) $_REQUEST[self::REQUEST_EVENT_ID] : $event_id;
 		if (!$this->getEventData($event_id, $event_data, $parser_data)) return false;
 		$data = array(
 			'show_details' 	=> ($show_details) ? 1 : 0,
 			'event'					=> $parser_data
 		);
 		return $this->getTemplate('frontend.view.id.htt', $data);
 	} // viewEventID()

 	/**
 	 *
 	 * Enter description here ...
 	 */
 	public function viewEventDay() {
 		global $dbEvent;
 		global $dbEventGroup;

 		if (!isset($_REQUEST[self::REQUEST_DAY]) || !isset($_REQUEST[self::REQUEST_MONTH]) || !isset($_REQUEST[self::REQUEST_YEAR])) {
 			// keine Parameter gesetzt - aktuelles Datum verwenden!
 			$month = date('n');
 			$day = date('j');
 			$year = date('Y');
 		}
 		else {
	 		$month = (int) $_REQUEST[self::REQUEST_MONTH];
	 		$day = (int) $_REQUEST[self::REQUEST_DAY];
	 		$year = (int) $_REQUEST[self::REQUEST_YEAR];
 		}

 		$search_date_from = date('Y-m-d H:i:s', mktime(23,59,59,$month,$day-1,$year));
 		$search_date_to = date('Y-m-d H:i:s', mktime(0,0,0,$month,$day+1,$year));
 		$dt = mktime(0,0,0,$month,$day,$year);

 		$weekdays = explode(',', event_cfg_day_names);
 		$months = explode(',', event_cfg_month_names);

 		$day = array(
 			'date'					=> date(event_cfg_date_str, $dt),
 			'day'						=> date('j', $dt),
 			'day_zero'			=> date('d', $dt),
 			'day_name'			=> trim($weekdays[date('w', $dt)]),
 			'day_name_2'		=> substr(trim($weekdays[date('w', $dt)]), 1, 2),
 			'month_name'		=> trim($months[date('n', $dt)-1]),
 			'month_name_3'	=> substr(trim($months[date('n', $dt)-1]), 1, 3),
 			'month'					=> date('n', $dt),
 			'month_zero'		=> date('m', $dt),
 			'year'					=> date('Y', $dt),
 			'year_2'				=> date('y', $dt),
 			'week'					=> date('W', $dt),
 			'link_start'		=> $this->page_link
 		);

 		$filter_group = '';
 		$group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
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
 		$event_items = array();
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$event_items[] = $parser_data;
 		}
 		$show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
 		$data = array(
 			'day'						=> $day,
 			'show_details'	=> ($show_details) ? 1 : 0,
 			'events'				=> (count($events) > 0) ? $event_items : NULL
 		);
 		return $this->getTemplate('frontend.view.day.htt', $data);
 	} // viewEventDay()

 	public function viewEventMonth() {
 		global $dbEvent;
 		global $dbEventGroup;

 		if (!isset($_REQUEST[self::REQUEST_MONTH]) || !isset($_REQUEST[self::REQUEST_YEAR])) {
 			// keine Parameter gesetzt - aktuelles Datum verwenden!
 			$month = date('n');
 			$year = date('Y');
 		}
 		else {
	 		$month = (int) $_REQUEST[self::REQUEST_MONTH];
	 		$year = (int) $_REQUEST[self::REQUEST_YEAR];
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
 		$data_month = array(
 			'month'									=> $month,
 			'month_zero'						=> date('m', $dt),
 			'month_name'						=> $months[$month-1],
 			'month_name_3'					=> substr($months[$month-1], 1, 3),
 			'year'									=> $year,
 			'year_2'								=> date('y', $dt),
 			'last_day'							=> date ('j', mktime(0, 0, 0, $month+1, 0, $year)),
 			'prev_month'						=> $prev_month+1,
 			'prev_month_zero'				=> sprintf('%02d', $prev_month+1),
 			'prev_month_name'				=> $months[$prev_month],
 			'prev_month_name_3'			=> substr($months[$prev_month], 1, 3),
 			'next_month'						=> $next_month,
 			'next_month_zero'				=> sprintf('%02d', $next_month),
 			'next_month_name'				=> $months[$next_month],
 			'next_month_name_3'			=> substr($months[$next_month], 1, 3),
 			'link_start'						=> $this->page_link,
 			'link_prev_month'				=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s',
 																					$this->page_link,
 																					self::REQUEST_ACTION,
 																					self::ACTION_EVENT,
 																					self::REQUEST_EVENT,
 																					self::VIEW_MONTH,
 																					self::REQUEST_MONTH,
 																					$prev_month+1,
 																					self::REQUEST_YEAR,
 																					$prev_year),
 		  'link_next_month'				=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s',
 																					$this->page_link,
 																					self::REQUEST_ACTION,
 																					self::ACTION_EVENT,
 																					self::REQUEST_EVENT,
 																					self::VIEW_MONTH,
 																					self::REQUEST_MONTH,
 																					$next_month+1,
 																					self::REQUEST_YEAR,
 																					$next_year)
 		);
 		$filter_group = '';
 		$group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
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
 		$event_items = array();
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$event_items[] = $parser_data;
 		}
 		$show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
 		$data = array(
 			'show_details'		=> ($show_details) ? 1 : 0,
 			'month'						=> $data_month,
 			'events'					=> (count($events) > 0) ? $event_items : NULL
 		);
		return $this->getTemplate('frontend.view.month.htt', $data);
 	} // viewEventMonth()

 	public function viewEventWeek() {
 		global $dbEvent;
 		global $dbEventGroup;

 		if (!isset($_REQUEST[self::REQUEST_DAY]) || !isset($_REQUEST[self::REQUEST_MONTH]) || !isset($_REQUEST[self::REQUEST_YEAR])) {
 			// keine Parameter gesetzt - aktuelles Datum verwenden!
 			$month = date('n');
 			$day = date('j');
 			$year = date('Y');
 		}
 		else {
	 		$month = (int) $_REQUEST[self::REQUEST_MONTH];
	 		$day = (int) $_REQUEST[self::REQUEST_DAY];
	 		$year = (int) $_REQUEST[self::REQUEST_YEAR];
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

 		$week = array(
 			'monday'					=> date('j', $dt),
 			'monday_zero'			=> date('d', $dt),
 			'sunday'					=> date('j', mktime(0,0,0,$month,$monday+6,$year)),
 			'sunday_zero'			=> date('d', mktime(0,0,0,$month,$monday+6,$year)),
 			'week'						=> (int) date('W', $dt),
 			'week_zero'				=> date('W', $dt),
 			'year'						=> date('Y', $dt),
 			'year_2'					=> date('y', $dt),
 			'month'						=> date('n', $dt),
 			'month_zero'			=> date('m', $dt),
 			'month_name'			=> $months[date('n')-1],
 			'month_name_3'		=> substr($months[date('n')-1], 1, 3),
 			'link_prev_week'	=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s&%s=%s',
 																		$this->page_link,
 																		self::REQUEST_ACTION,
 																		self::ACTION_EVENT,
 																		self::REQUEST_EVENT,
 																		self::VIEW_WEEK,
 																		self::REQUEST_MONTH,
 																		date('n', $prev_date),
 																		self::REQUEST_DAY,
 																		date('j', $prev_date),
 																		self::REQUEST_YEAR,
 																		date('Y', $prev_date)),
 			'link_next_week'	=> sprintf(	'%s?%s=%s&%s=%s&%s=%s&%s=%s&%s=%s',
 																		$this->page_link,
 																		self::REQUEST_ACTION,
 																		self::ACTION_EVENT,
 																		self::REQUEST_EVENT,
 																		self::VIEW_WEEK,
 																		self::REQUEST_MONTH,
 																		date('n', $next_date),
 																		self::REQUEST_DAY,
 																		date('j', $next_date),
 																		self::REQUEST_YEAR,
 																		date('Y', $next_date)),
 			'link_start'			=> $this->page_link,
 		);

 		$filter_group = '';
 		$group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
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
 		$event_items = array();
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$event_items[] = $parser_data;
 		}
 		$show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
 		$data = array(
 			'show_details'	=> ($show_details) ? 1 : 0,
 			'events'				=> (count($events) > 0) ? $event_items : NULL,
 			'week'					=> $week
 		);
 		return $this->getTemplate('frontend.view.week.htt', $data);
 	} // viewEventWeek()

 	public function viewEventActive() {
 		global $dbEvent;
 		global $dbEventGroup;

 		$search_date_from = date('Y-m-d H:i:s', mktime(23,59,59,date('n'),date('j')-1,date('Y')));
 		$search_date_to = date('Y-m-d H:i:s', mktime(23,59,59,date('n'),date('j'),date('Y')));
 		$months = explode(',', event_cfg_month_names);

 		$filter_group = '';
 		$group = (isset($_REQUEST[self::PARAM_GROUP]) && !empty($_REQUEST[self::PARAM_GROUP])) ? $_REQUEST[self::PARAM_GROUP] : $this->params[self::PARAM_GROUP];
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
 			$this->setMessage(sprintf(event_msg_no_event_at_date, $months[date('n')-1]));//$months[$month-1]));
 			return $this->getMessage();
 		}
 		$event_items = array();
 		foreach ($events as $event) {
 			if (!$this->getEventData($event[dbEvent::field_id], $event_data, $parser_data)) return false;
 			$event_items[] = $parser_data;
 		}
 		$show_details = (isset($_REQUEST[self::PARAM_VIEW])) ? (bool) $_REQUEST[self::PARAM_VIEW] : $this->params[self::PARAM_DETAIL];
 		$data = array(
 			'events' 				=> $event_items,
 			'show_details'	=> ($show_details) ? 1 : 0
 		);
 		return $this->getTemplate('frontend.view.active.htt', $data);
 	} // viewEventActive

} // class eventFrontend

?>