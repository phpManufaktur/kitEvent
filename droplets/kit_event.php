<?php
//:interface to kitEvent
//:Please visit http://phpManufaktur.de for informations about kitEvent!
/**
 * kitEvent
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */
if (file_exists(WB_PATH.'/modules/kit_event/class.frontend.php')) {
	require_once(WB_PATH.'/modules/kit_event/class.frontend.php');
	$event = new eventFrontend();
	$params = $event->getParams();
	$params[eventFrontend::param_view] = (isset($view)) ? strtolower(trim($view)) : eventFrontend::view_all;
	$event->setParams($params);
	return $event->action();
}
else {
	return "kitEvent is not installed!";
}