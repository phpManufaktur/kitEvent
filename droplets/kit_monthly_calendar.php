//:Show a monthly calender and indicate events. On click the details of the event will be shown
//:Please visit http://phpManufaktur.de for informations about kitEvent!
/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/de/addons/kitevent.php
 * @copyright 2011-2012 phpManufaktur by Ralf Hertsch
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License (GPL)
 */

if (file_exists(WB_PATH.'/modules/kit_event/class.calendar.php')) {
	require_once(WB_PATH.'/modules/kit_event/class.calendar.php');
	$calendar = new monthlyCalendar();
	$params = $calendar->getParams();
	$params[monthlyCalendar::param_inactive_days] = (isset($inactive_days) && strtolower($inactive_days) == 'true') ? true : false;
  $params[monthlyCalendar::param_navigation] = (isset($navigation) && strtolower($navigation) == 'true') ? true : false;
  $params[monthlyCalendar::param_show_today] = (isset($show_today) && strtolower($show_today) == 'true') ? true : false;
  $params[monthlyCalendar::param_show_weeks] = (isset($show_weeks) && strtolower($show_weeks) == 'true') ? true : false;
  $params[monthlyCalendar::param_response_id] = (isset($response_id)) ? $response_id : -1;
  $params[monthlyCalendar::param_ignore_topics] = (isset($ignore_topics) && strtolower($ignore_topics) == 'true') ? true : false;
  $params[monthlyCalendar::param_select_month] = (isset($month)) ? (int) $month : 0;
  $params[monthlyCalendar::param_select_year] = (isset($year)) ? (int) $year : 0;
  $params[monthlyCalendar::param_group] = (isset($group)) ? $group : '';
  $params[monthlyCalendar::param_action] = (isset($action)) ? $action : monthlyCalendar::action_show_month;
  $params[monthlyCalendar::param_preset] = (isset($preset)) ? $preset : 1;
  $params[monthlyCalendar::param_link_month] = (isset($link_month) && (strtolower($link_month) == 'true')) ? true : false;
  $calendar->setParams($params);
  return $calendar->action();
}
else {
	return "kitEvent is not installed!";
}
