<?php

/**
 * kitTools
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/de/addons/kittools.php
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
class kitEventToolsLibrary {
  const unkownUser = 'UNKNOWN USER';
  const unknownEMail = 'unknown@user.tld';
  private $error;

  /**
   * Constructor
   */
  public function __construct() {
    $this->error = '';
  } // __construct()

  /**
   * sets the $error
   *
   * @param string $error
   */
  public function setError($error) {
    $this->error = $error;
  }

  /**
   * get the $error
   *
   * @return string
   */
  public function getError() {
    return $this->error;
  }

  /**
   * return true if an error is set
   *
   * @return boolean
   */
  public function isError() {
    return (bool) !empty($this->error);
  }

  /**
   * Return Version of the class
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.php');
    if ($info_text == false) {
      return -1;
    }
    // walk through array
    foreach ($info_text as $item) {
      if (strpos($item, '$module_version') !== false) {
        // split string $module_version
        $value = split('=', $item);
        // return floatval
        return floatval(ereg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
      }
    }
    return -1;
  } // getVersion()

  /**
   * Check if the LEPTON frontend is active
   *
   * @return boolean
   */
  public function isFrontendActive() {
    // Wenn Session Variable gesetzt ist, diese verwenden...
    if (isset($_SESSION['FRONTEND'])) {
      $_SESSION['FRONTEND'] == 'active' ? $result = true : $result = false;
    }
    else {
      // Ansonsten wird geprueft ob die class admin geladen ist.
      // Methode hat den Haken, dass sie nicht zuverlaessig funktioniert,
      // wenn der angemeldete User gleichzeitig das Front- und Backend
      // geoeffnet hat.
      $classes = get_declared_classes();
      in_array('admin', $classes) ? $result = false : $result = true;
    }
    return $result;
  } // isFrontendActive()

  /**
   * check if the user is logged in and authenticated
   *
   * @return boolean
   */
  public function isAuthenticated() {
    global $wb;
    global $admin;
    if ($this->isFrontendActive()) {
      return (bool) $wb->is_authenticated();
    }
    else {
      return (bool) $admin->is_authenticated();
    }
  } // isAuthenticated()

  /**
   * Read the WebsiteBaker or LEPTON settings and return them in a array
   * $settings
   *
   * @param
   *          array reference &$settings
   * @return boolean
   */
  public function getWBSettings(&$settings) {
    global $database;
    global $sql_result;
    $settings = array();
    $thisQuery = "SELECT * FROM " . TABLE_PREFIX . "settings";
    $oldErrorReporting = error_reporting(0);
    $sql_result = $database->query($thisQuery);
    error_reporting($oldErrorReporting);
    if ($database->is_error()) {
      $this->error = sprintf('[%s - %s] SETTINGS: %s', __METHOD__, __LINE__, $database->get_error());
      return false;
    }
    else {
      for($i = 0; $i < $sql_result->numRows(); $i++) {
        $dummy = $sql_result->fetchRow();
        $settings[$dummy['name']] = $dummy['value'];
      }
      return true;
    }
  } // getWBSettings()

  /**
   * get the username in front- or in backend
   *
   * @return string
   */
  public function getUsername() {
    global $wb;
    global $admin;
    if ($this->isFrontendActive()) {
      if ($wb->is_authenticated()) {
        return $wb->get_username();
      }
      else {
        return self::unkownUser;
      }
    }
    else {
      if ($admin->is_authenticated()) {
        return $admin->get_username();
      }
      else {
        return self::unkownUser;
      }
    }
  } // getUsername()

  /**
   * return the display name in front- or backend
   *
   * @return string
   */
  public function getDisplayName() {
    global $wb;
    global $admin;
    if ($this->isFrontendActive()) {
      if (is_object($wb)) {
        if ($wb->is_authenticated()) {
          return $wb->get_display_name();
        }
        else {
          return self::unkownUser;
        }
      }
      else {
        return self::unkownUser;
      }
    }
    elseif (is_object($admin)) {
      if ($admin->is_authenticated()) {
        return $admin->get_display_name();
      }
      else {
        return self::unkownUser;
      }
    }
    else {
      return self::unkownUser;
    }
  } // getDisplayName()

  /**
   * return the email of the authenticated user
   *
   * @return string
   */
  public function getUserEMail() {
    global $wb;
    global $admin;
    if ($this->isFrontendActive()) {
      if ($wb->is_authenticated()) {
        return $wb->get_email();
      }
      else {
        return self::unknownEMail;
      }
    }
    else {
      if ($admin->is_authenticated()) {
        return $admin->get_email();
      }
      else {
        return self::unknownEMail;
      }
    }
  } // getUserEMail()

  /**
   * Korrigiert Backslashs in Slashs um z.B.
   * aus einer Windows
   * Pfadangabe eine Linux taugliche Pfadangabe zu erstellen
   *
   * @param string $this_path
   * @return string
   */
  public function correctBackslashToSlash($path) {
    return trim(str_replace("\\", "/", $path));
  }

  /**
   * Haengt einen Slash an das Ende des uebergebenen Strings
   * wenn das letzte Zeichen noch kein Slash ist
   *
   * @param string $path
   * @return string
   */
  public function addSlash($path) {
    $path = substr($path, strlen($path) - 1, 1) == "/" ? $path : $path . "/";
    return $path;
  }

  /**
   * removes a leading backslash from $path
   *
   * @param string $path
   * @return string $path
   */
  public function removeLeadingSlash($path) {
    $path = substr($path, 0, 1) == "/" ? substr($path, 1, strlen($path)) : $path;
    return $path;
  }

  /**
   * Gibt den vollstaendigen Pfad auf das /MEDIA Verzeichnis mit
   * abschliessendem Slash zurueck
   *
   * @return string
   */
  public function getMediaPath() {
    $result = $this->addSlash(WB_PATH) . $this->removeLeadingSlash($this->addSlash(MEDIA_DIRECTORY));
    return $result;
  }

  /**
   * Ermittelt den Realname Link einer Seite an Hand der $page_id
   *
   * @param integer $pageID
   * @param
   *          string reference &$fileName
   * @return boolean
   */
  public function getFileNameByPageID($pageID, &$fileName) {
    global $database;
    global $sql_result;

    $fileName = 'ERROR';
    $settings = array();
    if (!$this->getWBSettings($settings)) return false;
    $thisQuery = "SELECT * FROM " . TABLE_PREFIX . "pages WHERE page_id='$pageID'";
    $oldErrorReporting = error_reporting(0);
    $sql_result = $database->query($thisQuery);
    error_reporting($oldErrorReporting);
    if ($database->is_error()) {
      $this->error = sprintf('[%s - %s] PAGES: %s', __METHOD__, __LINE__, $database->get_error());
      return false;
    }
    elseif ($sql_result->numRows() > 0) {
      // alles OK, Daten uebernehmen
      $thisArr = $sql_result->fetchRow();
      if (is_file(WB_PATH . $settings['pages_directory'] . $thisArr['link'] . $settings['page_extension'])) {
        $fileName = $this->removeLeadingSlash($thisArr['link'] . $settings['page_extension']);
        return true;
      }
      else {
        $this->error = sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(tool_error_link_by_page_id, $pageID));
        return false;
      }
    }
    else {
      // keine Daten
      $this->error = sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(tool_error_link_row_empty, $pageID));
      return false;
    }
  } // getFileNameByPageID

  /**
   * Ermittelt die URL einer Seite an Hand der $page_id
   *
   * @param integer $pageID
   * @param
   *          string reference $url
   * @return boolean
   */
  public function getUrlByPageID($pageID, &$url, $ignore_topics = false) {
    global $database;
    if (defined('TOPIC_ID') && !$ignore_topics) {
      // es handelt sich um eine TOPICS Seite
      $SQL = sprintf("SELECT link FROM %smod_topics WHERE topic_id='%d'", TABLE_PREFIX, TOPIC_ID);
      if (false !== ($link = $database->get_one($SQL))) {
        // include TOPICS settings
        global $topics_directory;
        include WB_PATH . '/modules/topics/module_settings.php';
        $url = WB_URL . $topics_directory . $link . PAGE_EXTENSION;
      }
      else {
        return false;
      }
    }
    elseif ($this->getFileNameByPageID($pageID, $url)) {
      $url = WB_URL . PAGES_DIRECTORY . '/' . $url;
    }
    else {
      return false;
    }
    return true;
  }

  /**
   * Erzeugt einen Link fuer die als page_id angegebene Seite.
   * Parameter werden als Array in der Form 'KEY' => 'VALUE' uebergeben.
   *
   * @param integer $pageID
   * @param
   *          string reference $link
   * @param array $params
   * @return boolean
   */
  public function getPageLinkByPageID($pageID, &$link, $params = array()) {
    $link = '';
    // Link fuer Frontend erzeugen
    if (!$this->getUrlByPageID($pageID, $link)) return false;
    $start = true;
    foreach ($params as $key => $value) {
      if ($start) {
        $start = false;
        $link .= "?$key=$value";
      }
      else {
        $link .= "&$key=$value";
      }
    }
    return true;
  } // getPageLinkByPageID

  /**
   * Generiert ein neues Passwort der Laenge $length
   *
   * @param integer $length
   * @return string
   */
  public function generatePassword($length = 7) {
    $new_pass = '';
    $salt = 'abcdefghjkmnpqrstuvwxyz123456789';
    srand((double) microtime() * 1000000);
    $i = 0;
    while ($i <= $length) {
      $num = rand() % 33;
      $tmp = substr($salt, $num, 1);
      $new_pass = $new_pass . $tmp;
      $i++;
    }
    return $new_pass;
  } // generatePassword()

  /**
   * Wandelt einen String in einen Float Wert um.
   * Verwendet per Default die deutschen Trennzeichen
   *
   * @param string $string
   * @param string $thousand_separator
   * @param string $decimal_separator
   * @return float
   */
  public function str2float($string, $thousand_separator = '.', $decimal_separator = ',') {
    $string = str_replace($thousand_separator, '', $string);
    $string = str_replace($decimal_separator, '.', $string);
    $float = floatval($string);
    return $float;
  }

  /**
   * Wandelt einen String in einen Integer Wert um.
   * Verwendet per Default die deutschen Trennzeichen
   *
   * @param string $string
   * @param string $thousand_separator
   * @param string $decimal_separator
   * @return integer
   */
  public function str2int($string, $thousand_separator = '.', $decimal_separator = ',') {
    $string = str_replace('.', '', $string);
    $string = str_replace(',', '.', $string);
    $int = intval($string);
    return $int;
  }

  /**
   * Ueberprueft die uebergebene E-Mail Adresse auf logische Gueltigkeit
   *
   * @param string $email
   * @return boolean
   */
  public function validateEMail($email) {
    if (preg_match("/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/i", $email)) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Formatiert einen BYTE Wert in einen lesbaren Wert und gibt
   * einen Byte, KB, MB oder GB String zurueck
   *
   * @param integer $byte
   * @return string
   */
  public function bytes2Str($byte) {
    if ($byte < 1024) {
      $ergebnis = round($byte, 2) . ' Byte';
    }
    elseif ($byte >= 1024 and $byte < pow(1024, 2)) {
      $ergebnis = round($byte / 1024, 2) . ' KB';
    }
    elseif ($byte >= pow(1024, 2) and $byte < pow(1024, 3)) {
      $ergebnis = round($byte / pow(1024, 2), 2) . ' MB';
    }
    elseif ($byte >= pow(1024, 3) and $byte < pow(1024, 4)) {
      $ergebnis = round($byte / pow(1024, 3), 2) . ' GB';
    }
    elseif ($byte >= pow(1024, 4) and $byte < pow(1024, 5)) {
      $ergebnis = round($byte / pow(1024, 4), 2) . ' TB';
    }
    elseif ($byte >= pow(1024, 5) and $byte < pow(1024, 6)) {
      $ergebnis = round($byte / pow(1024, 5), 2) . ' PB';
    }
    elseif ($byte >= pow(1024, 6) and $byte < pow(1024, 7)) {
      $ergebnis = round($byte / pow(1024, 6), 2) . ' EB';
    }
    return $ergebnis;
  } // bytes2Str()

  /**
   * Wandelt einen Dateinamen der Sonderzeichen, Umlaute und/oder
   * Leerzeichen enthaelt in einen Linux faehigen Dateinamen um
   *
   * @param string $string
   * @return string
   */
  public function cleanFileName($string) {
    $string = entities_to_7bit($string);
    // Now replace spaces with page spcacer
    $string = trim($string);
    $string = preg_replace('/(\s)+/', '_', $string);
    // Now remove all bad characters
    $bad = array(
      '\'', /* /  */ '"', /* " */ '<', /* < */  '>', /* > */
              '{', /* { */  '}', /* } */  '[', /* [ */  ']', /* ] */  '`', /* ` */
              '!', /* ! */  '@', /* @ */  '#', /* # */  '$', /* $ */  '%', /* % */
              '^', /* ^ */  '&', /* & */  '*', /* * */  '(', /* ( */  ')', /* ) */
              '=', /* = */  '+', /* + */  '|', /* | */  '/', /* / */  '\\', /* \ */
              ';', /* ; */  ':', /* : */  ',', /* , */  '?' /* ? */
      );
    $string = str_replace($bad, '', $string);
    // Now convert to lower-case
    $string = strtolower($string);
    // If there are any weird language characters, this will protect us against
    // possible problems they could cause
    $string = str_replace(array(
      '%2F',
      '%'
    ), array(
      '/',
      ''
    ), urlencode($string));
    // Finally, return the cleaned string
    return $string;
  } // cleanFileName

  /**
   * Generate a globally unique identifier (GUID)
   * Uses COM extension under Windows otherwise
   * create a random GUID in the same style
   *
   * @return string $guid
   */
  public function createGUID() {
    if (function_exists('com_create_guid')) {
      $guid = com_create_guid();
      $guid = strtolower($guid);
      if (strpos($guid, '{') == 0) {
        $guid = substr($guid, 1);
      }
      if (strpos($guid, '}') == strlen($guid) - 1) {
        $guid = substr($guid, 0, strlen($guid) - 2);
      }
      return $guid;
    }
    else {
      return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
  } // createGUID()

  /**
   * Remove the directory $target and all content
   *
   * @param string $target
   *
   * @return boolean
   */
  public function removeDirectory($target) {
    // is a file specified?
    if (is_file($target)) {
      if (is_writable($target)) {
        if (@unlink($target)) {
          return true;
        }
      }
      return false;
    }
    // target is directory?
    if (is_dir($target)) {
      if (is_writeable($target)) {
        foreach (new DirectoryIterator($target) as $_res) {
          if ($_res->isDot()) {
            unset($_res);
            continue;
          }
          if ($_res->isFile()) {
            $this->removeDirectory($_res->getPathName());
          }
          elseif ($_res->isDir()) {
            $this->removeDirectory($_res->getRealPath());
          }
          unset($_res);
        } // foreach
        if (@rmdir($target)) {
          return true;
        }
      }
      return false;
    } // is_dir()
  } // removeDirectory()

  /**
   * Find files at the $location with the extension $fileregex
   * and return them as array
   * Expl.: findfile(WB_PATH, $fileregex='/\.(php|inc)$/')
   * returns all files with extension *.php and *.inc
   *
   * @param string $location
   * @param string $fileregex
   *
   * @return array
   */
  public function findfile($location = '', $fileregex = '') {
    if (!$location or !is_dir($location) or !$fileregex) {
      return false;
    }
    $matchedfiles = array();
    $all = opendir($location);
    while (false !== ($file = readdir($all))) {
      if (is_dir($location . '/' . $file) and $file != ".." and $file != ".") {
        $subdir_matches = $this->findfile($location . '/' . $file, $fileregex);
        $matchedfiles = array_merge($matchedfiles, $subdir_matches);
        unset($file);
      }
      elseif (!is_dir($location . '/' . $file)) {
        if (preg_match($fileregex, $file)) {
          array_push($matchedfiles, $location . '/' . $file);
        }
      }
    }
    closedir($all);
    unset($all);
    return $matchedfiles;
  } // findFile()

  /**
   * Return a String with $maxLength, cutting with respecting space
   * between words
   *
   * @param string $string
   * @param integer $maxLength
   * @param boolean $space
   *
   * @return string
   */
  public function strCutting($string, $maxLength, $space = true) {
    if (strlen($string) > $maxLength) {
      $result = "";
      $string = substr($string, 0, $maxLength - 4);
      if ($space) {
        $words = split(" ", $string);
        for($i = 0; $i < count($words) - 1; $i++) {
          $result .= $words[$i] . " ";
        }
      }
      else {
        $result = $string;
      }
      return $result . "...";
    }
    else {
      return $string;
    }
  } // strCutting()
  public function convertBytes($value) {
    if (is_numeric($value)) {
      return $value;
    }
    else {
      $value_length = strlen($value);
      $qty = substr($value, 0, $value_length - 1);
      $unit = strtolower(substr($value, $value_length - 1));
      switch ($unit) :
        case 'k' :
          $qty *= 1024;
          break;
        case 'm' :
          $qty *= 1048576;
          break;
        case 'g' :
          $qty *= 1073741824;
          break;
      endswitch
      ;
      return $qty;
    }
  } // convertBytes

} // class kitEventToolsLibrary

