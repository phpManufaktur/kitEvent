//:Show a monthly calender and indicate events. On click the details of the event will be shown
//:Please visit http://phpManufaktur.de for informations about kitEvent!
/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

if (file_exists(WB_PATH.'/modules/kit_event/class.calendar.php')) {
	require_once(WB_PATH.'/modules/kit_event/class.calendar.php');
	$calendar = new monthlyCalendar();
	$params = $calendar->getParams();
	$params[monthlyCalendar::PARAM_INACTIVE_DAYS] = (isset($inactive_days) && strtolower($inactive_days) == 'true') ? true : false;
  $params[monthlyCalendar::PARAM_NAVIGATION] = (isset($navigation) && strtolower($navigation) == 'true') ? true : false;
  $params[monthlyCalendar::PARAM_SHOW_TODAY] = (isset($show_today) && strtolower($show_today) == 'true') ? true : false;
  $params[monthlyCalendar::PARAM_SHOW_WEEKS] = (isset($show_weeks) && strtolower($show_weeks) == 'true') ? true : false;
  $params[monthlyCalendar::PARAM_RESPONSE_ID] = (isset($response_id)) ? $response_id : -1;
  $params[monthlyCalendar::PARAM_IGNORE_TOPICS] = (isset($ignore_topics) && strtolower($ignore_topics) == 'true') ? true : false;
  $params[monthlyCalendar::PARAM_SELECT_MONTH] = (isset($month)) ? (int) $month : 0;
  $params[monthlyCalendar::PARAM_SELECT_YEAR] = (isset($year)) ? (int) $year : 0;
  $params[monthlyCalendar::PARAM_GROUP] = (isset($group)) ? $group : '';
  $params[monthlyCalendar::PARAM_ACTION] = (isset($action)) ? $action : monthlyCalendar::action_show_month;
  $params[monthlyCalendar::PARAM_PRESET] = (isset($preset)) ? $preset : 1;
  $params[monthlyCalendar::PARAM_LINK_MONTH] = (isset($link_month) && (strtolower($link_month) == 'true')) ? true : false;
  $params[monthlyCalendar::PARAM_DEBUG] = (isset($debug) && strtolower($debug) == 'true') ? true : false;
  $calendar->setParams($params);
  return $calendar->action();
}
else {
	return "kitEvent is not installed!";
}
