<?php
/**
 * kitEvent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

if (file_exists(WB_PATH.'/modules/libraryadmin/include.php') && (isset($tablesorter) && strtolower($tablesorter) == 'true')) {
    // load the jQuery tableSorter if needed
    include_once WB_PATH.'/modules/libraryadmin/include.php';
    $new_page = includePreset($wb_page_data, 'lib_jquery', 'tableSorter', 'kit_event', NULL, false, NULL, NULL );
    if (!empty($new_page))
      $wb_page_data = $new_page;
}

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
  // inactive, not in use! $params[eventFrontend::PARAM_RESPONSE_ID] = (isset($response_id)) ? $response_id : -1;
  $params[eventFrontend::PARAM_IGNORE_TOPICS] = (isset($ignore_topics) && strtolower($ignore_topics) == 'true') ? true : false;
  $params[eventFrontend::PARAM_COUNTRY] = (isset($country)) ? strtoupper($country) : '';
  $params[eventFrontend::PARAM_CITY] = (isset($city)) ? $city : '';
  $params[eventFrontend::PARAM_ZIP] = (isset($zip)) ? $zip : '';
  $params[eventFrontend::PARAM_ORDER_BY] = (isset($order_by)) ? $order_by : '';
  $params[eventFrontend::PARAM_SORT] = (isset($sort) && (strtoupper($sort) == 'DESC')) ? 'DESC' : 'ASC';
  $params[eventFrontend::PARAM_CATEGORY] = (isset($category)) ? $category : '';
  $params[eventFrontend::PARAM_DATE] = (isset($date)) ? $date : '';
  $params[eventFrontend::PARAM_MONTH] = (isset($month)) ? $month : '';
  $params[eventFrontend::PARAM_YEAR] = (isset($year)) ? $year : '';
  $params[eventFrontend::PARAM_REGION] = (isset($region)) ? $region : '';
  $event->setParams($params);
  return $event->action();
}
else {
  return "kitEvent is not installed!";
}