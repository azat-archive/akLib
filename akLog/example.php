<?

/**
 * Exapmle of akLog
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once dirname(__FILE__) . '/../main.php';
require_once 'akLog.class.php';

// if from CLI
if (PHP_SAPI != 'cli') echo '<pre>';

$l = akLog::getInstance();
for ($i = 0; $i < 100; $i++) {
	$l->add(array('test' . $i, 'testFile' . $i, $i));
}

// if from CLI
if (PHP_SAPI != 'cli') echo '</pre>';
