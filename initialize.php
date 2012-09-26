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

// for extended error reporting set to true!
if (!defined('KIT_DEBUG')) define('KIT_DEBUG', false);

// Prompt all errors and use own error_handler
if (KIT_DEBUG == true) {
	ini_set('error_reporting', E_ALL);
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