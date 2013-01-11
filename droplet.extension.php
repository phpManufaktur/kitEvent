<?php

/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 - 2012
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

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.frontend.php');

if (!function_exists('kit_event_droplet_search')) {
	function kit_event_droplet_search($page_id, $page_url) {
		global $parser;
		global $database;

		$tke = TABLE_PREFIX.'mod_kit_event';
		$tkei = TABLE_PREFIX.'mod_kit_event_item';
		$SQL = "SELECT * FROM `$tke`, `$tkei` WHERE $tke.item_id=$tkei.item_id AND `evt_status`='1' ORDER BY `evt_event_date_from` DESC";
		if (null === ($query = $database->query($SQL))) {
		  trigger_error(sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $database->get_error()), E_USER_ERROR);
		  return false;
		}
		$events = array();
		while (false !== ($event = $query->fetchRow(MYSQL_ASSOC)))
		  $events[] = $event;

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
	    $frontend->getEventData($event['evt_id'], $event_data, $parser_data);
			$result[] = array(
				'url'	=> $page_url,
				'params' => http_build_query(array(
				    eventFrontend::REQUEST_ACTION	=> eventFrontend::ACTION_EVENT,
					  eventFrontend::REQUEST_EVENT => eventFrontend::VIEW_ID,
						eventFrontend::REQUEST_EVENT_ID	=> $event['evt_id'],
						eventFrontend::REQUEST_EVENT_DETAIL => 1
				    )),
				'title'	=> $parser->get($tpl_title, array(
				    'date_time' => sprintf('%s h', date(CFG_DATETIME_STR, strtotime($event['evt_event_date_from']))),
						'title'			=> $event['item_title'])),
				'description'	=> $parser->get($tpl_description, array(
				    'description' => !empty($event['item_desc_short']) ? eventFrontend::unsanitizeText($event['item_desc_short']) : $event['item_title'],
            'event' => $parser_data)),
				'text' => eventFrontend::unsanitizeText($event['item_desc_short']).' '.eventFrontend::unsanitizeText($event['item_desc_long']).' '.
			      $event['group_id'].' '.$event['item_location'].' '.$event['item_desc_link'].' '.
			      eventFrontend::unsanitizeText($event['item_free_1']).' '.eventFrontend::unsanitizeText($event['item_free_2']).' '.
			      eventFrontend::unsanitizeText($event['item_free_3']).' '.eventFrontend::unsanitizeText($event['item_free_4']).' '.
			      eventFrontend::unsanitizeText($event['item_free_5']),
				'modified_when'	=> strtotime($event['evt_timestamp']),
				'modified_by'	=> 1 // admin
			);
		}
		return  $result;
	} // kit_event_droplet_search()
}

if (!function_exists('kit_event_droplet_header')) {
	function kit_event_droplet_header($page_id) {
		global $database;

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

			$tke = TABLE_PREFIX.'mod_kit_event';
			$tkei = TABLE_PREFIX.'mod_kit_event_item';
			$SQL = "SELECT * FROM `$tke`, `$tkei` WHERE $tke.item_id=$tkei.item_id AND `evt_id`='$event_id'";
			if (null === ($query = $database->query($SQL))) {
			  trigger_error(sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $database->get_error()), E_USER_ERROR);
			  return false;
			}

			if ($query->numRows() > 0) {
				$event = $query->fetchRow(MYSQL_ASSOC);
				$result = array(
					'title'				=> isset($event['item_title']) ? strip_tags($event['item_title']) : '',
					'description'	=> isset($event['item_desc_short']) ? substr(strip_tags($event['item_desc_short']), 0, 180) : '',
					'keywords'		=> '' // noch nicht unterstuetzt...
				);
			}
		}

		return $result;
	} // kit_event_droplet_header
}

?>