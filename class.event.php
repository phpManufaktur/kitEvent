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

// include dbConnectLE
if (!class_exists('dbConnectLE')) require_once(WB_PATH.'/modules/dbconnect_le/include.php');

class dbEvent extends dbConnectLE {

	const field_id									= 'evt_id';
	const field_event_item					= 'item_id';
	const field_event_group					= 'group_id';
	const field_event_date_from			= 'evt_event_date_from';
	const field_event_date_to				= 'evt_event_date_to';
	const field_publish_date_from		= 'evt_publish_date_from';
	const field_publish_date_to			= 'evt_publish_date_to';
	const field_participants_max		= 'evt_participants_max';
	const field_participants_total	= 'evt_participants_total';
	const field_deadline						= 'evt_deadline';
	const field_perma_link					= 'evt_perma_link';
	const field_ical_file						= 'evt_ical_file';
	const field_qrcode_image				= 'evt_qrcode_image';
	const field_status							= 'evt_status';
	const field_timestamp						= 'evt_timestamp';

	const status_active							= 1;
	const status_locked							= 0;
	const status_deleted						= -1;
	const status_archived						= 2;

	public $status_array = array(
		self::status_active			=> 'ACTIVE',
		self::status_locked			=> 'LOCKED',
		self::status_deleted		=> 'DELETED'
	);

	private $createTables 		= false;

  public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_kit_event');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_event_item, "INT(11) NOT NULL DEFAULT '-1'");
  	$this->addFieldDefinition(self::field_event_group, "INT(11) NOT NULL DEFAULT '-1'");
  	$this->addFieldDefinition(self::field_event_date_from, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_event_date_to, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_publish_date_from, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_publish_date_to, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_participants_max, "INT(11) NOT NULL DEFAULT '-1'");
  	$this->addFieldDefinition(self::field_participants_total, "INT(11) NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_deadline, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_perma_link, "VARCHAR(128) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_ical_file, "VARCHAR(32) NOT NULl DEFAULT ''");
  	$this->addFieldDefinition(self::field_qrcode_image, "VARCHAR(32) NOT NULl DEFAULT ''");
  	$this->addFieldDefinition(self::field_status, "TINYINT NOT NULL DEFAULT '".self::status_active."'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->setIndexFields(array(self::field_event_item, self::field_event_group));
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()

} // class dbEvent


class dbEventItem extends dbConnectLE {

	const field_id									= 'item_id';
	const field_title								= 'item_title';
	const field_desc_short					= 'item_desc_short';
	const field_desc_long						= 'item_desc_long';
	const field_desc_link						= 'item_desc_link';
	const field_location						= 'item_location';
	const field_costs								= 'item_costs';
	const field_free_1              = 'item_free_1';
	const field_free_2              = 'item_free_2';
	const field_free_3              = 'item_free_3';
	const field_free_4              = 'item_free_4';
	const field_free_5              = 'item_free_5';
	const field_timestamp						= 'item_timestamp';

	private $createTables 		= false;

  public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_kit_event_item');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_title, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_desc_short, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_desc_long, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_desc_link, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_location, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_costs, "FLOAT NOT NULL DEFAULT '-1'");
  	$this->addFieldDefinition(self::field_free_1, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_free_2, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_free_3, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_free_4, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_free_5, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()

} // class dbEventItem

class dbEventGroup extends dbConnectLE {

	const field_id									= 'group_id';
	const field_name								= 'group_name';
	const field_redirect_page				= 'group_redirect_page';
	const field_perma_link_pattern	= 'group_perma_pattern';
	const field_desc								= 'group_desc';
	const field_status							= 'group_status';
	const field_timestamp						= 'group_timestamp';

	const status_active							= 1;
	const status_locked							= 0;
	const status_deleted						= -1;

	public $status_array = array(
		self::status_active 	=> 'ACTIVE',
		self::status_locked		=> 'LOCKED',
		self::status_deleted	=> 'DELETED'
	);

	private $createTables 		= false;

  public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_kit_event_group');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_name, "VARCHAR(64) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_redirect_page, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_perma_link_pattern, "VARCHAR(128) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_desc, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_status, "TINYINT NOT NULL DEFAULT '".self::status_active."'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()

} // class dbEventGroup

class dbEventOrder extends dbConnectLE {

	const field_id						= 'ord_id';
	const field_event_id			= 'evt_id';
	const field_order_date		= 'ord_date';
	const field_title					= 'ord_title';
	const field_first_name		= 'ord_first_name';
	const field_last_name			= 'ord_last_name';
	const field_company				= 'ord_company';
	const field_street				= 'ord_street';
	const field_zip						= 'ord_zip';
	const field_city					= 'ord_city';
	const field_email					= 'ord_email';
	const field_phone					= 'ord_phone';
	const field_best_time			= 'ord_best_time';
	const field_message				= 'ord_message';
	const field_confirm_order	= 'ord_confirm';
	const field_send_mail			= 'ord_send_mail';
	const field_free_1				= 'ord_free_1';
	const field_free_2				= 'ord_free_2';
	const field_free_3				= 'ord_free_3';
	const field_free_4				= 'ord_free_4';
	const field_free_5				= 'ord_free_5';
	const field_timestamp			= 'ord_timestamp';

	private $createTables 		= false;

  public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_kit_event_order');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
		$this->addFieldDefinition(self::field_event_id, "INT(11) NOT NULL DEFAULT '-1'");
		$this->addFieldDefinition(self::field_order_date, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$this->addFieldDefinition(self::field_first_name, "VARCHAR(80) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_last_name, "VARCHAR(80) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_title, "VARCHAR(40) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_company, "VARCHAR(80) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_street, "VARCHAR(80) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_zip, "VARCHAR(10) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_city, "VARCHAR(80) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_email, "VARCHAR(255) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_phone, "VARCHAR(80) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_best_time, "VARCHAR(255) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_message, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_confirm_order, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$this->addFieldDefinition(self::field_send_mail, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
		$this->addFieldDefinition(self::field_free_1, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_free_2, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_free_3, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_free_4, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_free_5, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()

} // class dbEventOrder
