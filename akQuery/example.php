<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Exapmle of akQuery
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once dirname(__FILE__) . '/../main.php';
require_once 'akQuery.class.php';

// if from CLI
if (PHP_SAPI != 'cli') echo '<pre>';

/// ========= SELECT's =========

// one query
echo akQuery::select('*')->from('testing') . "\n";

// multi query
$query = akQuery::select('*')->from('testing a');
$query->join('joinerTable b')->on('a.id = b.id')->limit(3);
echo $query . "\n";

// or the same in one
echo akQuery::select('*')->from('testing a')->join('joinerTable b')->on('a.id = b.id')->limit(3) . "\n";

/// ========= INSERT's =========
echo akQuery::insert('insertionTable')->set('field1 = "1", field2 = "2"') . "\n";

/// ========= UPDATE's =========
echo akQuery::update('updateTable')->set('field1 = "1", field2 = "2"')->where('field3 = ""') . "\n";

/// ========= DELETE's =========
echo akQuery::delete()->from('deleteTable')->where('field3 = ""')->limit(1) . "\n";

// if from CLI
if (PHP_SAPI != 'cli') echo '</pre>';
