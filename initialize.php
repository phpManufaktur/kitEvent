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

function kit_error_handler($level, $message, $file, $line) {
	switch ($level):
		case 1:			$type = 'E_ERROR'; break;
		case 2:			$type = 'E_WARNING'; break;
		case 4:			$type = 'E_PARSE'; break;
		case 8:			$type = 'E_NOTICE'; break;
		case 16: 		$type = 'E_CORE_ERROR';	break;
		case 32: 		$type = 'E_CORE_WARNING';	break;
		case 64: 		$type = 'E_COMPILE_ERROR'; break;
		case 128:		$type = 'E_COMPILE_WARNING'; break;
		case 256:		$type = 'E_USER_ERROR';	break;
		case 512:		$type = 'E_USER_WARNING';	break;
		case 1024: 	$type = 'E_USER_NOTICE'; break;
		case 2047: 	$type = 'E_ALL'; break;
		case 2048: 	$type = 'E_STRICT';	break;
		default:		$type = $level;	break;
	endswitch;
	echo sprintf(	'<div style="margin:5px 15px;padding:10px;border:1px solid #000;color:#000;background-color:#ffd;">'.
								'<table width="99%%"><colgroup><col width="120" /><col width="*" /></colgroup>'.
								'<tr><td>Type</td><td style="font-weight:bold;color:red;">%s</td></tr><tr><td>Message</td><td style="color:red;">%s</td></tr>'.
								'<tr><td style="vertical-align:top;">Line:File</td><td><b>%s</b> : <i>%s</i></td></tr></table></div>', 
								$type, $message, $line, $file);
}
// Prompt all errors and use own error_handler
ini_set('error_reporting', E_ALL);
set_error_handler("kit_error_handler");

// include language file
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden 
	define('KIT_EVT_LANGUAGE', 'DE'); // die Konstante gibt an in welcher Sprache KIT Event aktuell arbeitet
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
	define('KIT_EVT_LANGUAGE', LANGUAGE); // die Konstante gibt an in welcher Sprache KIT Event aktuell arbeitet
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
global $dbEventOrder;

if (!is_object($parser)) $parser = new Dwoo();
if (!is_object($dbEvent)) $dbEvent = new dbEvent();
if (!is_object($dbEventCfg)) $dbEventCfg = new dbEventCfg();
if (!is_object($dbEventGroup)) $dbEventGroup = new dbEventGroup();
if (!is_object($dbEventItem)) $dbEventItem = new dbEventItem();
if (!is_object($dbEventOrder)) $dbEventOrder = new dbEventOrder();

?>