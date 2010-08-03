<?

/**
 * Exapmle of akDispatcher
 * 
 * .htaccess file is already exists,
 * but this script bust be in /akDispatcher/example.php in reference to DOCUMENT_ROOT
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once dirname(__FILE__) . '/../main.php';
require_once 'akDispatcher.class.php';

// testing functions
function a() { var_dump(func_get_args(), __FUNCTION__); }
function c() { var_dump(func_get_args(), __FUNCTION__); }
// the right function
function b() { return '<br />' . __FUNCTION__ . '<br />'; }
function multi() {
	return sprintf(
		'<br /> %s => %s <br /> %s => %s <br />', 
		'first', akDispatcher::getInstance()->getParam('first'),
		'second', akDispatcher::getInstance()->getParam('second')
	);
}

// dispatcher
$dispatcher = akDispatcher::getInstance();
$dispatcher->setCallbacks(function() { return 'before' . "\n"; }, function() { return 'after' . "\n"; }, function () { return 'default' . "\n"; });

$dispatcher->add('/akDispatcher/test/:param1/a', 'a');
$dispatcher->add('/akDispatcher/te[]st/c/:param1/:param2', 'c');
// the right events
$dispatcher->add('/akDispatcher/test/b', 'b');
$dispatcher->add('/akDispatcher/test/:first_:second', 'multi'); // see akDispatcher::additionalParamDelimiter

$dispatcher->run();
