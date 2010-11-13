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

class a {
	function a_a() {}
	function b_a() { return $b_a_result; }
}

function pop_up() {
}

class a_a {
	const const_test = 'TEST_CONST';
	
	static function test_func_static() {}
	public function test_func() {
		return self::test_func_static();
	}
}

a_a::test_func_static();
a_a::const_test;
$a = new a_a();
$a->test_func();

var_dump($_SERVER);
test_test();

?>
NOT_REPLACE_THIS();
<?
but_this_replace();
?>