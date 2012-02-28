/**
 * kitEvent
 * 
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011-2012
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License (GPL)
 * @version $Id: backend.js 15 2011-10-10 06:47:27Z phpmanufaktur $
 */

function execOnChange(target_url, select_id) { 
  var x;
  x = target_url + document.getElementById(select_id).value;
  document.body.style.cursor='wait';
  window.location = x;
  return false;	
}
