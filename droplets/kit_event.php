//:interface to kitEvent
//:Please visit http://phpManufaktur.de for informations about kitEvent!
/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

if (file_exists(WB_PATH.'/modules/kit_event/class.frontend.php')) {
	require_once(WB_PATH.'/modules/kit_event/class.frontend.php');
	$event = new eventFrontend();
	$params = $event->getParams();
	$params[eventFrontend::PARAM_VIEW] = (isset($view)) ? strtolower(trim($view)) : eventFrontend::VIEW_ACTIVE;
	$params[eventFrontend::PARAM_PRESET] = (isset($preset)) ? (int) $preset : 1;
	$params[eventFrontend::PARAM_DETAIL] = (isset($detail) && (strtolower($detail) == 'true')) ? true : false;
	$params[eventFrontend::PARAM_GROUP] = (isset($group) && !empty($group)) ? $group : '';
	$params[eventFrontend::PARAM_EVENT_ID] = (isset($event_id) && !empty($event_id)) ? $event_id : -1;
	$params[eventFrontend::PARAM_SEARCH] = (isset($search) && strtolower($search) == 'false') ? false : true;
  $params[eventFrontend::PARAM_HEADER] = (isset($header) && strtolower($header) == 'true') ? true : false;
  $params[eventFrontend::PARAM_CSS] = (isset($load_css) && strtolower($load_css) == 'false') ? false : true;
  $params[eventFrontend::PARAM_DEBUG] = (isset($debug) && strtolower($debug) == 'true') ? true : false;
  $event->setParams($params);
	return $event->action();
}
else {
	return "kitEvent is not installed!";
}