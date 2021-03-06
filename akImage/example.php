<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Exapmle of akCaptcha
 * 
 * Image are already exists just run this script!
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 */

require_once __DIR__ . '/../main.php';
require_once 'akImage.class.php';

$path = realpath(__DIR__) . '/';

/// ========= Using GD =========
$image = akImage::getInstance($path . 'example.png');
$image->usingImagick = false;
$image->resize(50, 50, 'exampleResizeGD.png');
$image->rotate(90, $path . 'exampleRotatedGD.png');

/// ========= Using Imagick =========
$image->usingImagick = true;
$image->resize(50, 50, 'exampleResizeImagick.png');
$image->rotate(90, $path . 'exampleRotatedImagick.png');
