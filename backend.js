/**
 * kitEvent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011-2012 phpManufaktur by Ralf Hertsch
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License (GPL)
 */

function execOnChange(target_url, select_id) { 
  var x;
  x = target_url + document.getElementById(select_id).value;
  document.body.style.cursor='wait';
  window.location = x;
  return false;	
}
