//:Show a monthly calender and indicate events. On click the details of the event will be shown
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

if (file_exists(WB_PATH.'/modules/kit_event/class.calendar.php')) {
	require_once(WB_PATH.'/modules/kit_event/class.calendar.php');
	$calendar = new monthlyCalendar();
	$params = $calendar->getParams();
	$params[monthlyCalendar::param_inactive_days] = (isset($inactive_days) && strtolower($inactive_days) == 'true') ? true : false;
  $params[monthlyCalendar::param_navigation] = (isset($navigation) && strtolower($navigation) == 'true') ? true : false;
  $params[monthlyCalendar::param_show_today] = (isset($show_today) && strtolower($show_today) == 'true') ? true : false;
  $params[monthlyCalendar::param_show_weeks] = (isset($show_weeks) && strtolower($show_weeks) == 'true') ? true : false;
  $params[monthlyCalendar::param_response_id] = (isset($response_id)) ? $response_id : -1;
  $calendar->setParams($params);
  return $calendar->action();
}
else {
	return "kitEvent is not installed!";
}
