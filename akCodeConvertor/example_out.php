<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Example of code for akCodeConvertor
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 */

class a{
	function aA() {}
	function bA() { return $bAResult; }
}

function popUp() {
}

class aA() {
	function s() {}
}

aA::s();
$a = new aA();
$a->s();

aA::s();
$aA = new aA();
$aA->s();

var_dump($_SERVER);
function_exists('a');
if (!function_exists('file_put_contents')) {
	var_dump('nope');
}

testTest();
