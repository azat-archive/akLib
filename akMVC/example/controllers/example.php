<?

/**
 * Exapmle of akMVC
 * Controller example
 * 
 * @author Azat Khuzhin
 */

akMVC::getInstance()->requireModel('example.php');

function exampleCallback($int = null) {
	akMVC::getInstance()->set('header', 'It`s works!');
	akMVC::getInstance()->set('content', example::test($int));
	
	return akMVC::getInstance()->content('example.php');
}