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

// wb2lepton compatibility
if (!defined('LEPTON_PATH'))
  require_once WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/wb2lepton.php';

// use LEPTON 2.x I18n for access to language files
if (!class_exists('LEPTON_Helper_I18n'))
  require_once LEPTON_PATH.'/modules/manufaktur_config/framework/LEPTON/Helper/I18n.php';

global $I18n;
if (!is_object($I18n))
  $I18n = new LEPTON_Helper_I18n();

if (file_exists(LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/'.LANGUAGE.'.php')) {
  $I18n->addFile(LANGUAGE.'.php', LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
}

// load language depending configuration file
if (file_exists(LEPTON_PATH.'/modules/manufaktur_config/languages/'.LANGUAGE.'.cfg.php'))
  require_once LEPTON_PATH.'/modules/manufaktur_config/languages/'.LANGUAGE.'.cfg.php';
else
  require_once LEPTON_PATH.'/modules/manufaktur_config/languages/EN.cfg.php';

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.event.php');


global $admin;
global $database;

$error = '';

// delete tables
$tables = array('mod_kit_event','mod_kit_event_group','mod_kit_event_item','mod_kit_event_order');

foreach ($tables as $table) {
	$database->query("DROP TABLE IF EXISTS `".TABLE_PREFIX."$table`");
	if ($database->is_error())
	  $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}

// delete the kitEvent Droplets
$droplets = array('kit_event', 'kit_monthly_calendar');

foreach ($droplets as $droplet) {
  $database->query("DELETE FROM `".TABLE_PREFIX."mod_droplets` WHERE `name`='$droplet'");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error, 'javascript:history_back();');
}

?>