<?

/**
 * Exapmle of akCaptcha
 * 
 * Image are already exists just run this script!
 * 
 * @licence GPLv2
 * 
 * @author Azat Khuzhin
 */

require_once dirname(__FILE__) . '/../main.php';
require_once 'akImage.class.php';

$path = realpath(dirname(__FILE__)) . '/';

/// ========= Using GD =========
$image = akImage::getInstance($path . 'example.jpg');
$image->usingImagick = false;
$image->resize(50, 50, 'exampleResizeGD.jpg');
$image->rotate(90, $path . 'exampleRotatedGD.jpg');

/// ========= Using Imagick =========
$image->usingImagick = true;
$image->resize(50, 50, 'exampleResizeImagick.jpg');
$image->rotate(90, $path . 'exampleRotatedImagick.jpg');
