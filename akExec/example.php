<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Exapmle of akCaptcha
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once dirname(__FILE__) . '/../main.php';
require_once 'akExec.class.php';

/// ========= NON-A-Sync =========
var_dump(akExec::getInstance()->quickStart('date +%s')); // get unixTimeStamp

/// ========= A-Sync =========
akExec::getInstance()->start('php -r "sleep(10);"', false);
while (akExec::getInstance()->isRuning()) {
	echo 'Process still runing' . "\n";
	sleep(1);
}
echo 'Process terminated' . "\n";
