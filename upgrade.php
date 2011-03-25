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