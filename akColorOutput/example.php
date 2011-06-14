<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Exapmle of akColorOutput
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 */

require_once __DIR__ . '/../main.php';
require_once 'akColorOutput.class.php';

akColorOutput::getInstance()->setColor(akColorOutput::COLOR_RED)->printf('dev');
