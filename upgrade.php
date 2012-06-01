<?php

/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/de/addons/kitevent.php
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

// include language file
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden
	define('KIT_EVT_LANGUAGE', 'DE'); // die Konstante gibt an in welcher Sprache KIT Event aktuell arbeitet
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
	define('KIT_EVT_LANGUAGE', LANGUAGE); // die Konstante gibt an in welcher Sprache KIT Event aktuell arbeitet
}

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.event.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.droplets.php');

global $admin;

$error = '';

// Release 0.12
$dbEventOrder = new dbEventOrder();
if (!$dbEventOrder->sqlFieldExists(dbEventOrder::field_free_1)) {
	// freie Felder hinzufuegen
	$insert_fields = array(dbEventOrder::field_free_1, dbEventOrder::field_free_2, dbEventOrder::field_free_3, dbEventOrder::field_free_4, dbEventOrder::field_free_5);
	foreach ($insert_fields as $iField) {
		if (!$dbEventOrder->sqlAlterTableAddField($iField, "TEXT NOT NULL DEFAULT ''")) {
			$error .= sprintf('[UPGRADE] %s', $dbEventOrder->getError());
			break;
		}
	}
}

// Release 0.28
$dbEventGroup = new dbEventGroup();
if (!$dbEventGroup->sqlFieldExists(dbEventGroup::field_perma_link_pattern)) {
	// permaLink Pattern hinzufuegen
	if (!$dbEventGroup->sqlAlterTableAddField(dbEventGroup::field_perma_link_pattern, "VARCHAR(128) NOT NULL DEFAULT ''", dbEventGroup::field_name)) {
		$error .= sprintf('[UPGRADE] %s', $dbEventGroup->getError());
	}
}
if (!$dbEventGroup->sqlFieldExists(dbEventGroup::field_redirect_page)) {
	// permaLink Pattern hinzufuegen
	if (!$dbEventGroup->sqlAlterTableAddField(dbEventGroup::field_redirect_page, "VARCHAR(255) NOT NULL DEFAULT ''", dbEventGroup::field_perma_link_pattern)) {
		$error .= sprintf('[UPGRADE] %s', $dbEventGroup->getError());
	}
}
$dbEvent = new dbEvent();
if (!$dbEvent->sqlFieldExists(dbEvent::field_perma_link)) {
	// permaLink hinzufuegen
	if (!$dbEvent->sqlAlterTableAddField(dbEvent::field_perma_link, "VARCHAR(128) NOT NULL DEFAULT ''", dbEvent::field_deadline)) {
		$error .= sprintf('[UPGRADE] %s', $dbEvent->getError());
	}
}
if (!$dbEvent->sqlFieldExists(dbEvent::field_ical_file)) {
	// Feld fuer iCal hinzufuegen
	if (!$dbEvent->sqlAlterTableAddField(dbEvent::field_ical_file, "VARCHAR(32) NOT NULL DEFAULT ''", dbEvent::field_perma_link)) {
		$error .= sprintf('[UPGRADE] %s', $dbEvent->getError());
	}
}
if (!$dbEvent->sqlFieldExists(dbEvent::field_qrcode_image)) {
	// Feld fuer QR Code hinzufuegen
	if (!$dbEvent->sqlAlterTableAddField(dbEvent::field_qrcode_image, "VARCHAR(32) NOT NULL DEFAULT ''", dbEvent::field_ical_file)) {
		$error .= sprintf('[UPGRADE] %s', $dbEvent->getError());
	}
}

// remove Droplets
$dbDroplets = new dbDroplets();
$droplets = array('kit_monthly_calendar', 'kit_event');
foreach ($droplets as $droplet) {
	$where = array(dbDroplets::field_name => $droplet);
	if (!$dbDroplets->sqlDeleteRecord($where)) {
		$message = sprintf('[UPGRADE] Error uninstalling Droplet: %s', $dbDroplets->getError());
	}
}

// Install Droplets
$droplets = new checkDroplets();
if ($droplets->insertDropletsIntoTable()) {
  $message = 'The Droplets for kitEventCalendar where successfully installed! Please look at the Help for further informations.';
}
else {
  $message = 'The installation of the Droplets for kitEventCalendar failed. Error: '. $droplets->getError();
}
if ($message != "") {
  echo '<script language="javascript">alert ("'.$message.'");</script>';
}

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}


?>