<?

/**
 * Exapmle of akSysQuery
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once dirname(__FILE__) . '/../main.php';
require_once 'akSysQuery.class.php';

// if from CLI
if (PHP_SAPI != 'cli') echo '<pre>';

/// ========= ALTER =========
echo akSysQuery::alter('tableExample', 'DROP notNeedField');


// if from CLI
if (PHP_SAPI != 'cli') echo '</pre>';
