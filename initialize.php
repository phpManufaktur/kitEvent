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

// for extended error reporting set to true!
if (!defined('KIT_DEBUG')) define('KIT_DEBUG', false);

function kit_event_error_handler($level, $message, $file, $line) {
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
if (KIT_DEBUG == true) {
	ini_set('error_reporting', E_ALL);
	set_error_handler("kit_event_error_handler");
}

// include GENERAL language file
if(!file_exists(WB_PATH .'/modules/kit_tools/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/kit_tools/languages/DE.php'); // Vorgabe: DE verwenden
}
else {
	require_once(WB_PATH .'/modules/kit_tools/languages/' .LANGUAGE .'.php');
}

// include language file
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden
	define('KIT_EVT_LANGUAGE', 'DE'); // die Konstante gibt an in welcher Sprache KIT Event aktuell arbeitet
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
	define('KIT_EVT_LANGUAGE', LANGUAGE); // die Konstante gibt an in welcher Sprache KIT Event aktuell arbeitet
}

if (!class_exists('dbconnectle')) 				require_once(WB_PATH.'/modules/dbconnect_le/include.php');
if (!class_exists('kitToolsLibrary'))   	require_once(WB_PATH.'/modules/kit_tools/class.tools.php');

if (!class_exists('Dwoo')) {
  require_once WB_PATH.'/modules/dwoo/include.php';
}

// set cache and compile path for the template engine
$cache_path = WB_PATH.'/temp/cache';
if (!file_exists($cache_path)) mkdir($cache_path, 0755, true);
$compiled_path = WB_PATH.'/temp/compiled';
if (!file_exists($compiled_path)) mkdir($compiled_path, 0755, true);

// init the template engine
global $parser;
if (!is_object($parser)) $parser = new Dwoo($compiled_path, $cache_path);

// load extensions for the template engine
$loader = $parser->getLoader();
$loader->addDirectory(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/plugins/');

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.event.php');

global $dbEvent;
global $dbEventCfg;
global $dbEventGroup;
global $dbEventItem;
global $dbEventOrder;
global $kitLibrary;

if (!is_object($dbEvent)) $dbEvent = new dbEvent();
if (!is_object($dbEventCfg)) $dbEventCfg = new dbEventCfg();
if (!is_object($dbEventGroup)) $dbEventGroup = new dbEventGroup();
if (!is_object($dbEventItem)) $dbEventItem = new dbEventItem();
if (!is_object($dbEventOrder)) $dbEventOrder = new dbEventOrder();
if (!is_object($kitLibrary)) $kitLibrary = new kitToolsLibrary();

?>