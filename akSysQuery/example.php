<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

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
