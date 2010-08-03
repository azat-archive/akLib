<?

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
