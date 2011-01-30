<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Exapmle of akMVC
 * 
 * .htaccess file is already exists,
 * but this script bust be in /akDispatcher/example.php in reference to DOCUMENT_ROOT
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once __DIR__ . '/../../main.php';
require_once '../akMVC.class.php';

$mvc = akMVC::getInstance();
$mvc->setPaths('akMVC/example/models/', 'akMVC/example/views/', 'akMVC/example/controllers/');
// the right event
$mvc->add('/akMVC/example/test/:num', 'example.php', 'exampleCallback');
$mvc->add('/akMVC/example/whatYourWhant*', 'example.php', 'exampleCallback'); // quantifier

$mvc->run();
