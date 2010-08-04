<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

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