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
	function a_a() {}
	function b_a() { return $b_a_result; }
}

function pop_up() {
}

class a_a() {
	function s() {}
}

a_a::s();
$a = new a_a();
$a->s();

a_a::s();
$a_a = new a_a();
$a_a->s();

var_dump($_SERVER);
function_exists('a');
if (!function_exists('file_put_contents')) {
	var_dump('nope');
}

test_test();
