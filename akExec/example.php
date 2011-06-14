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
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 */

require_once __DIR__ . '/../main.php';
require_once 'akExec.class.php';

/// ========= NON-A-Sync =========
echo "========= NON-A-Sync =========\n";
var_dump(akExec::getInstance()->quickStart('date +%s')); // get unixTimeStamp

/// ========= A-Sync =========
echo "========= A-Sync =========\n";
akExec::getInstance()->terminate();
akExec::getInstance()->start('sleep 5');
while (akExec::getInstance()->isRuning()) {
	echo 'Process still runing' . "\n";
	sleep(1);
}
echo 'Process terminated' . "\n";

/// ========= A-Sync with callbacks=========
echo "========= A-Sync with callbacks=========\n";
akExec::getInstance()->terminate();
function processExitFunction($status) {
	if ($status['exitcode'] !== 0) {
		printf("Process with PID %u not exit with EXIT_SUCCESS\n", $status['pid']);
	} else {
		printf("Process with PID %u exit with EXIT_SUCCESS\n", $status['pid']);
	}
}
akExec::getInstance()->start('sleep 2');
akExec::getInstance()->start('sleep 2');
akExec::getInstance()->loop('processExitFunction');
echo 'Process terminated' . "\n";
