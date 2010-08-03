<?

/**
 * akEmptyLog - Empty log (for no conditions before logging)
 * This class contains empty functions-wrappers
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @see akException
 */

require_once 'sys/akException.class.php';

class akEmptyLog {
	function add() {}
	function flush() {}
	function get() {}
	function cat() {}
}
