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

$error = '';

/**
 * Check if the specified $field in the $table exists
 *
 * @param string $table
 * @param string $field
 * @return boolean
 */
function fieldExists($table, $field) {
  global $database;
  global $admin;
  if (null === ($query = $database->query("DESCRIBE `".TABLE_PREFIX."$table`")))
    $admin->print_error($database->get_error());
  while (false !== ($data = $query->fetchRow(MYSQL_ASSOC)))
    if ($data['Field'] == $field) return true;
  return false;
} // sqlFieldExists()

/**
 * Iterate directory tree very efficient
 * Function postet from donovan.pp@gmail.com at
 * http://www.php.net/manual/de/function.scandir.php
 *
 * @param STR $dir
 * @return ARRAY - directoryTree
 */
function directoryTree($dir) {
  if (substr($dir,-1) == "/") $dir = substr($dir,0,-1);
  $path = array();
  $stack = array();
  $stack[] = $dir;
  while ($stack) {
    $thisdir = array_pop($stack);
    if (false !== ($dircont = scandir($thisdir))) {
        $i=0;
        while (isset($dircont[$i])) {
          if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
            $current_file = "{$thisdir}/{$dircont[$i]}";
            if (is_file($current_file)) {
              $path[] = "{$thisdir}/{$dircont[$i]}";
            }
            elseif (is_dir($current_file)) {
              $stack[] = $current_file;
            }
          }
          $i++;
        }
    }
  }
  return $path;
} // directoryTree()

function getSubDirectories($dir) {
  if (substr($dir,-1) == "/") $dir = substr($dir,0,-1);
  $path = array();
  $stack = array();
  $stack[] = $dir;
  while ($stack) {
    $thisdir = array_pop($stack);
    if (false !== ($dircont = scandir($thisdir))) {
        $i=0;
        while (isset($dircont[$i])) {
          if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
            $current_file = "{$thisdir}/{$dircont[$i]}";
            if (is_dir($current_file)) {
              $path[] = "{$thisdir}/{$dircont[$i]}";
              $stack[] = $current_file;
            }
          }
          $i++;
        }
    }
  }
  return $path;
} // getSubDirectories()

function __move_recursive($dirsource, $dirdest) {
  if (is_dir($dirsource)) {
    $dir_handle = opendir($dirsource);
  }
  while (false !== ($file = readdir($dir_handle))) {
    if ($file != "." && $file != "..") {
      if (!is_dir($dirsource."/".$file)) {
        copy($dirsource."/".$file, $dirdest.'/'.$file);
        unlink($dirsource.'/'.$file);
      }
      else {
        if (!is_dir($dirdest."/".$file)) {
          make_dir($dirdest."/".$file);
          __move_recursive($dirsource."/".$file, $dirdest.'/'.$file);
        }
        rm_full_dir($dirsource.'/'.$file);
      }
    }
  }
  closedir($dir_handle);
  return true;
}

// Release 0.28
if (!fieldExists('mod_kit_event_group', 'group_perma_pattern')) {
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_Event_group` ADD `group_perma_pattern` VARCHAR(128) NOT NULL DEFAULT '' AFTER `group_name`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}
if (!fieldExists('mod_kit_event_group', 'group_redirect_page')) {
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_Event_group` ADD `group_redirect_page` VARCHAR(255) NOT NULL DEFAULT '' AFTER `group_perma_pattern`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}
if (!fieldExists('mod_kit_event', 'evt_perma_link')) {
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event` ADD `evt_perma_link` VARCHAR(128) NOT NULL DEFAULT '' AFTER `evt_deadline`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}
if (!fieldExists('mod_kit_event', 'evt_ical_file')) {
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event` ADD `evt_ical_file` VARCHAR(32) NOT NULL DEFAULT '' AFTER `evt_perma_link`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}
if (!fieldExists('mod_kit_event', 'evt_qrcode_image')) {
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event` ADD `evt_qrcode_image` VARCHAR(32) NOT NULL DEFAULT '' AFTER `evt_ical_file`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}

// Release 0.40
if (!fieldExists('mod_kit_event_item', 'item_free_1')) {
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event_item` ADD `item_free_1` TEXT NOT NULL DEFAULT '' AFTER `item_costs`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event_item` ADD `item_free_2` TEXT NOT NULL DEFAULT '' AFTER `item_free_1`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event_item` ADD `item_free_3` TEXT NOT NULL DEFAULT '' AFTER `item_free_2`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event_item` ADD `item_free_4` TEXT NOT NULL DEFAULT '' AFTER `item_free_3`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
  $database->query("ALTER TABLE `".TABLE_PREFIX."mod_kit_event_item` ADD `item_free_5` TEXT NOT NULL DEFAULT '' AFTER `item_free_4`");
  if ($database->is_error())
    $error .= sprintf("<p>[UNINSTALL] %s</p>", $database->get_error());
}

if (file_exists(WB_PATH.'/modules/kit_event/htt')) {
  // check for individual presets and move them to the new template directory
  $directories = getSubDirectories(WB_PATH.'/modules/kit_event/htt');
  foreach ($directories as $directory) {
    $test = substr($directory, strlen(WB_PATH.'/modules/kit_event/htt/'));
    if (false !== strpos($test, DIRECTORY_SEPARATOR)) continue;
    if (intval($test) >= 100) {
      // move this individual preset to the new directory
      if (__move_recursive($directory, WB_PATH."/modules/kit_event/templates/frontend/presets/$test")) {
        // now we have to rename the .htt extensions to .dwoo
        $files = directoryTree(WB_PATH."/modules/kit_event/templates/frontend/presets/$test");
        foreach ($files as $file) {
          if (false !== strpos($file, '.htt')) {
            $rename = str_replace('.htt', '.dwoo', $file);
            rename($file, $rename);
          }
        }
      }
    }
  }
  rm_full_dir(WB_PATH.'/modules/kit_event/htt');
}

// delete no longer needed files
@unlink(WB_PATH.'/modules/kit_event/class.droplets.php');
@unlink(WB_PATH.'/modules/kit_event/frontend.css');
@unlink(WB_PATH.'/modules/kit_event/class.editor.php');
@unlink(WB_PATH.'/modules/kit_event/backend_body.js');
rm_full_dir(WB_PATH.'/modules/kit_event/include/jquery');

@unlink(WB_PATH.'/modules/kit_event/templates/backend/DE/config.dwoo');
@unlink(WB_PATH.'/modules/kit_event/class.event.php');

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
if (!$config->readXMLfile(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/config/kitEvent.xml', 'kit_event', false)) {
  $error .= $config->getError();
}

// Prompt Errors
if (!empty($error)) {
  $admin->print_error($error, 'javascript:history_back();');
}