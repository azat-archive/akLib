<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Exapmle of akMVC
 * Models example
 * 
 * @author Azat Khuzhin
 */

class example {
	static function test($num = 0) {
		return sprintf('I read %u from %s::%s', $num, __CLASS__, __FUNCTION__);
	}
}
