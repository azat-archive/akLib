<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Exapmle of akMySQLQuery
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once __DIR__ . '/../main.php';
require_once 'akMySQLQuery.class.php';

// if from CLI
if (PHP_SAPI != 'cli') echo '<pre>';

$m = akMySQLQuery::getInstance(null, null, 'azat', 'utf8', null, 'adminka_alon')->sprintf('SHOW TABLES LIKE "%s"', '%ve%');
var_dump($m);
$m = akMySQLQuery::getInstance()->sprintf('SHOW TABLES LIKE "%s"', '%ve%');
var_dump(akMySQLQuery::getInstance()->fetchAll($m));
// try to reconect
akMySQLQuery::getInstance()->reConnect();
// change db
akMySQLQuery::getInstance()->changeDB('mysql');
// non existed table
try {
	$m = akMySQLQuery::getInstance()->sprintf('SELECT * FROM users');
	var_dump(akMySQLQuery::getInstance()->fetchAll($m));
} catch (Exception $e) {
	var_dump('Prev error: ', $e->getMessage());
	
	$m = akMySQLQuery::getInstance()->sprintf('SELECT * FROM user');
	var_dump(akMySQLQuery::getInstance()->fetchAll($m));
}


// if from CLI
if (PHP_SAPI != 'cli') echo '</pre>';
