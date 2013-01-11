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

require_once WB_PATH.'/modules/manufaktur_config/library.php';

global $admin;
global $database;

$tables = array('dbEventGroup', 'dbEventItem');
$error = '';

foreach ($tables as $table) {
  $create = null;
  $create = new $table();
  if (!$create->sqlTableExists()) {
    if (!$create->sqlCreateTable()) {
      $error .= sprintf('[INSTALLATION %s] %s', $table, $create->getError());
    }
  }
}

// create mod_kit_event table
$SQL = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."mod_kit_event` ( ".
    "`evt_id` INT(11) NOT NULL AUTO_INCREMENT, ".
    "`item_id` INT(11) NOT NULL DEFAULT '-1', ".
    "`group_id` INT(11) NOT NULL DEFAULT '-1', ".
    "`evt_event_date_from` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`evt_event_date_to` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`evt_publish_date_from` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`evt_publish_date_to` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`evt_participants_max` INT(11) NOT NULL DEFAULT '-1', ".
    "`evt_participants_total` INT(11) NOT NULL DEFAULT '0', ".
    "`evt_deadline` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`evt_perma_link` VARCHAR(128) NOT NULL DEFAULT '', ".
    "`evt_ical_file` VARCHAR(32) NOT NULL DEFAULT '', ".
    "`evt_qrcode_image` VARCHAR(32) NOT NULL DEFAULT '', ".
    "`evt_status` TINYINT NOT NULL DEFAULT '1', ".
    "`evt_timestamp` TIMESTAMP, ".
    "PRIMARY KEY (`ord_id`), ".
    "KEY (`item_id`, `group_id`) ".
    ") ENGINE=MyIsam AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

if (!$database->query($SQL))
  $admin->print_error($database->get_error());

// create mod_kit_event_item table
$SQL = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."mod_kit_event_item` ( ".
    "`item_id` INT(11) NOT NULL AUTO_INCREMENT, ".
    "`item_title` VARCHAR(255) NOT NULL DEFAULT '', ".
    "`item_desc_short` TEXT NOT NULL DEFAULT '', ".
    "`item_desc_long` TEXT NOT NULL DEFAULT '', ".
    "`item_desc_link` VARCHAR(255) NOT NULL DEFAULT '', ".
    "`item_location` VARCHAR(255) NOT NULL DEFAULT '', ".
    "`item_costs` FLOAT NOT NULL DEFAULT '-1', ".
    "`item_free_1` TEXT NOT NULL DEFAULT '', ".
    "`item_free_2` TEXT NOT NULL DEFAULT '', ".
    "`item_free_3` TEXT NOT NULL DEFAULT '', ".
    "`item_free_4` TEXT NOT NULL DEFAULT '', ".
    "`item_free_5` TEXT NOT NULL DEFAULT '', ".
    "`item_timestamp` TIMESTAMP, ".
    "PRIMARY KEY (`ord_id`) ".
    ") ENGINE=MyIsam AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

if (!$database->query($SQL))
  $admin->print_error($database->get_error());

// create mod_kit_event_group table
$SQL = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."mod_kit_event_group` ( ".
    "`group_id` INT(11) NOT NULL AUTO_INCREMENT, ".
    "`group_name` VARCHAR(64) NOT NULL DEFAULT '', ".
    "`group_redirect_page` VARCHAR(255) NOT NULL DEFAULT '', ".
    "`group_perma_pattern` VARCHAR(128) NOT NULL DEFAULT '', ".
    "`group_desc` VARCHAR(255) NOT NULL DEFAULT '', ".
    "`group_status` TINYINT NOT NULL DEFAULT '1', ".
    "`group_timestamp` TIMESTAMP, ".
    "PRIMARY KEY (`ord_id`) ".
    ") ENGINE=MyIsam AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

if (!$database->query($SQL))
  $admin->print_error($database->get_error());

// create mod_kit_event_order table
$SQL = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."mod_kit_event_order` ( ".
    "`ord_id` INT(11) NOT NULL AUTO_INCREMENT, ".
    "`evt_id` INT(11) NOT NULL DEFAULT '-1', ".
    "`ord_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`ord_first_name` VARCHAR(80) NOT NULL DEFAULT '', ".
    "`ord_last_name` VARCHAR(80) NOT NULL DEFAULT '', ".
    "`ord_title` VARCHAR(40) NOT NULL DEFAULT '', ".
    "`ord_company` VARCHAR(80) NOT NULL DEFAULT '', ".
    "`ord_street` VARCHAR(80) NOT NULL DEFAULT '', ".
    "`ord_zip` VARCHAR(10) NOT NULL DEFAULT '', ".
    "`ord_city` VARCHAR(80) NOT NULL DEFAULT '', ".
    "`ord_email` VARCHAR(255) NOT NULL DEFAULT '', ".
    "`ord_phone` VARCHAR(80) NOT NULL DEFAULT '', ".
    "`ord_best_time` VARCHAR(255) NOT NULL DEFAULT '', ".
    "`ord_message` TEXT NOT NULL, ".
    "`ord_confirm` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`ord_send_mail` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
    "`ord_free_1` TEXT NOT NULL, ".
    "`ord_free_2` TEXT NOT NULL, ".
    "`ord_free_3` TEXT NOT NULL, ".
    "`ord_free_4` TEXT NOT NULL, ".
    "`ord_free_5` TEXT NOT NULL, ".
    "`ord_timestamp` TIMESTAMP, ".
    "PRIMARY KEY (`ord_id`) ".
    ") ENGINE=MyIsam AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

if (!$database->query($SQL))
  $admin->print_error($database->get_error());


// install or upgrade droplets
if (file_exists(WB_PATH.'/modules/droplets/functions.inc.php')) {
  include_once(WB_PATH.'/modules/droplets/functions.inc.php');
}

if (!function_exists('wb_unpack_and_import')) {
  function wb_unpack_and_import($temp_file, $temp_unzip) {
    global $admin, $database;

    // Include the PclZip class file
    require_once (WB_PATH . '/include/pclzip/pclzip.lib.php');

    $errors = array();
    $count = 0;
    $archive = new PclZip($temp_file);
    $list = $archive->extract(PCLZIP_OPT_PATH, $temp_unzip);
    // now, open all *.php files and search for the header;
    // an exported droplet starts with "//:"
    if (false !== ($dh = opendir($temp_unzip))) {
      while (false !== ($file = readdir($dh))) {
        if ($file != "." && $file != "..") {
          if (preg_match('/^(.*)\.php$/i', $file, $name_match)) {
            // Name of the Droplet = Filename
            $name = $name_match[1];
            // Slurp file contents
            $lines = file($temp_unzip . '/' . $file);
            // First line: Description
            if (preg_match('#^//\:(.*)$#', $lines[0], $match)) {
              $description = $match[1];
            }
            // Second line: Usage instructions
            if (preg_match('#^//\:(.*)$#', $lines[1], $match)) {
              $usage = addslashes($match[1]);
            }
            // Remaining: Droplet code
            $code = implode('', array_slice($lines, 2));
            // replace 'evil' chars in code
            $tags = array(
                '<?php',
                '?>',
                '<?'
            );
            $code = addslashes(str_replace($tags, '', $code));
            // Already in the DB?
            $stmt = 'INSERT';
            $id = NULL;
            $found = $database->get_one("SELECT * FROM " . TABLE_PREFIX . "mod_droplets WHERE name='$name'");
            if ($found && $found > 0) {
              $stmt = 'REPLACE';
              $id = $found;
            }
            // execute
            $result = $database->query("$stmt INTO " . TABLE_PREFIX . "mod_droplets VALUES('$id','$name','$code','$description','" . time() . "','" . $admin->get_user_id() . "',1,0,0,0,'$usage')");
            if (!$database->is_error()) {
              $count++;
              $imports[$name] = 1;
            }
            else {
              $errors[$name] = $database->get_error();
            }
          }
        }
      }
      closedir($dh);
    }
    return array(
        'count' => $count,
        'errors' => $errors,
        'imported' => $imports
    );
  } // function wb_unpack_and_import()
}

wb_unpack_and_import(WB_PATH.'/modules/kit_event/droplets/droplet_kit_event.zip', WB_PATH . '/temp/unzip/');
wb_unpack_and_import(WB_PATH.'/modules/kit_event/droplets/droplet_kit_monthly_calendar.zip', WB_PATH . '/temp/unzip/');

// initialize the configuration
$config = new manufakturConfig();
if (!$config->readXMLfile(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/config/kitEvent.xml', 'kit_event', true)) {
  $error .= $config->getError();
}


// Prompt Errors
if (!empty($error)) {
  $admin->print_error($error);
}