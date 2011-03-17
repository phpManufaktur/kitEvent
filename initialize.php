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

if (!defined('DEBUG_MODE')) define('DEBUG_MODE', true);

if (DEBUG_MODE) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}
else {
	ini_set('display_errors', 0);
	error_reporting(E_ERROR);
}

// include language file
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden 
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php'); 
}

if (!class_exists('dbconnectle')) require_once(WB_PATH.'/modules/dbconnect_le/include.php');
if (!class_exists('Dwoo')) 				require_once(WB_PATH.'/modules/dwoo/include.php');

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.event.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.tools.php');

global $parser;
global $dbEvent;
global $dbEventCfg;
global $dbEventGroup;
global $dbEventItem;

if (!is_object($parser)) $parser = new Dwoo();
if (!is_object($dbEvent)) $dbEvent = new dbEvent();
if (!is_object($dbEventCfg)) $dbEventCfg = new dbEventCfg();
if (!is_object($dbEventGroup)) $dbEventGroup = new dbEventGroup();
if (!is_object($dbEventItem)) $dbEventItem = new dbEventItem();

?>