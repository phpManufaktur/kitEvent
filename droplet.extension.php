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
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.frontend.php');

if (!function_exists('kit_event_droplet_search')) {
	function kit_event_droplet_search($page_id, $page_url) {
		global $dbEvent;
		global $dbEventItem;
		global $parser;

		$SQL = sprintf( "SELECT * FROM %s,%s WHERE %s.%s=%s.%s AND %s='%s' ORDER BY %s DESC",
										$dbEvent->getTableName(),
										$dbEventItem->getTableName(),
										$dbEvent->getTableName(),
										dbEvent::field_event_item,
										$dbEventItem->getTableName(),
										dbEventItem::field_id,
										dbEvent::field_status,
										dbEvent::status_active,
										dbEvent::field_event_date_from);
		$events = array();
		if (!$dbEvent->sqlExec($SQL, $events)) {
			trigger_error(sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $dbEvent->getError()), E_USER_ERROR);
			return false;
		}
		$result = array();
		$htt_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/templates/frontend/';
		if (file_exists($htt_path.'custom.search.result.title.dwoo'))
			$tpl_title = new Dwoo_Template_File($htt_path.'custom.search.result.title.dwoo');
		else
		  $tpl_title = new Dwoo_Template_File($htt_path.'search.result.title.dwoo');
		if (file_exists($htt_path.'custom.search.result.description.dwoo'))
		  $tpl_description = new Dwoo_Template_File($htt_path.'custom.search.result.description.dwoo');
		else 
			$tpl_description = new Dwoo_Template_File($htt_path.'search.result.description.dwoo');
	  $frontend = new eventFrontend();

		foreach ($events as $event) {
			$event_data = array();
			$parser_data = array();
	    $frontend->getEventData($event[dbEvent::field_id], $event_data, $parser_data);
			$result[] = array(
				'url'						=> $page_url,
				'params'				=> http_build_query(array(eventFrontend::REQUEST_ACTION 			=> eventFrontend::ACTION_EVENT,
																									eventFrontend::REQUEST_EVENT				=> eventFrontend::VIEW_ID,
																									eventFrontend::REQUEST_EVENT_ID			=> $event[dbEvent::field_id],
																									eventFrontend::REQUEST_EVENT_DETAIL => 1)),
				'title'					=> $parser->get($tpl_title, array('date_time' => sprintf('%s %s', date(event_cfg_datetime_str, strtotime($event[dbEvent::field_event_date_from])), event_text_hour),
																													'title'			=> $event[dbEventItem::field_title])),
				'description'		=> $parser->get($tpl_description, array('description' => strip_tags($event[dbEventItem::field_desc_short]),
	                                                              'event'       => $parser_data)),
				'text'					=> strip_tags($event[dbEventItem::field_desc_short]).' '.strip_tags($event[dbEventItem::field_desc_long]),
				'modified_when'	=> strtotime($event[dbEvent::field_timestamp]),
				'modified_by'		=> 1 // admin
			);
		}
		return  $result;
	} // kit_event_droplet_search()
}

if (!function_exists('kit_event_droplet_header')) {
	function kit_event_droplet_header($page_id) {
		global $dbEvent;
		global $dbEventItem;

		$result = array(
			'title'				=> '',
			'description'	=> '',
			'keywords'		=> ''
		);
		// Kopfdaten für Detailseiten von Events
		if ((isset($_REQUEST[eventFrontend::REQUEST_ACTION]) && ($_REQUEST[eventFrontend::REQUEST_ACTION] == eventFrontend::ACTION_EVENT)) &&
				(isset($_REQUEST[eventFrontend::REQUEST_EVENT]) && ($_REQUEST[eventFrontend::REQUEST_EVENT] == eventFrontend::VIEW_ID)) &&
				(isset($_REQUEST[eventFrontend::REQUEST_EVENT_ID])) &&
				(isset($_REQUEST[eventFrontend::REQUEST_EVENT_DETAIL]) && ($_REQUEST[eventFrontend::REQUEST_EVENT_DETAIL] == 1))) {
			$event_id = $_REQUEST[eventFrontend::REQUEST_EVENT_ID];
			$SQL = sprintf( "SELECT * FROM %s, %s WHERE %s.%s=%s.%s AND %s.%s='%s'",
											$dbEvent->getTableName(),
											$dbEventItem->getTableName(),
											$dbEvent->getTableName(),
											dbEvent::field_event_item,
											$dbEventItem->getTableName(),
											dbEventItem::field_id,
											$dbEvent->getTableName(),
											dbEvent::field_id,
											$event_id);
			$event = array();
			if (!$dbEvent->sqlExec($SQL, $event)) {
				trigger_error(sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $dbEvent->getError()), E_USER_ERROR);
				return false;
			}
			if (count($event) > 0) {
				$event = $event[0];
				$result = array(
					'title'				=> isset($event[dbEventItem::field_title]) ? strip_tags($event[dbEventItem::field_title]) : '',
					'description'	=> isset($event[dbEventItem::field_desc_short]) ? substr(strip_tags($event[dbEventItem::field_desc_short]), 0, 180) : '',
					'keywords'		=> '' // noch nicht unterstuetzt...
				);
			}
		}

		return $result;
	} // kit_event_droplet_header
}

?>