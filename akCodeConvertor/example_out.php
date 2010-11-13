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
	function aA() {}
	function bA() { return $bAResult; }
}

function popUp() {
}

class a_a {
	const const_test = 'TEST_CONST';
	
	static function testFuncStatic() {}
	public function testFunc() {
		return self::testFuncStatic();
	}
}

aA::testFuncStatic();
a_a::const_test;
$a = new aA();
$a->testFunc();

var_dump($_SERVER);
testTest();

?>
NOT_REPLACE_THIS();
<?
butThisReplace();
?>