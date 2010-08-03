<?

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

require_once dirname(__FILE__) . '/../../main.php';
require_once '../akMVC.class.php';

$mvc = akMVC::getInstance();
$mvc->setPaths('akMVC/example/models/', 'akMVC/example/views/', 'akMVC/example/controllers/');
// the right event
$mvc->add('/akMVC/example/test/:num', 'example.php', 'exampleCallback');

$mvc->run();
