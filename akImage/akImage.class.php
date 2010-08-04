<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akImage - Working with images
 * 
 * Can work with imagick CLI command (convert)
 * And with GD (bild-in php library)
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @link http://php.net/gd
 * @link http://www.imagemagick.org/script/index.php
 * 
 * @see akExec
 * @see akException
 */

require_once 'sys/akException.class.php';
require_once 'akExec/akExec.class.php';

class akImage {
	/**
	 * Imagick path
	 */
	const imagickPath = 'convert';
	/**
	 * Resize by width proportions
	 */
	const resizeByX = 1;
	/**
	 * Resize by height proportions
	 */
	const resizeByY = 2;
	/**
	 * Resize by width and height proportions
	 */
	const resizeByXY = 3;
	/**
	 * Avaliable exntensions
	 * 
	 * @TODO check last 3 extensions in imagick
	 * 
	 * @var array
	 */
	static $avaliableExtensions = array(
		'png', 'jpeg', 'gif', 'wbmp', 'xbm', 'xpm',
	);
	/**
	 * Image source path
	 * 
	 * @var string
	 */
	protected $srcPath;
	/**
	 * Using imagick or not
	 * 
	 * @var bool
	 */
	public $usingImagick = true;


	/**
	 * Constructor
	 * 
	 * @see class properties
	 * @return void
	 * 
	 * @throws akException
	 */
	public function __construct($path) {
		$this->srcPath = $path;
		
		if (!$this->srcPath || !is_readable($this->srcPath)) throw new akException('No image or image is not readable');
		if (!is_executable(self::imagickPath) && ((!extension_loaded('gd') && !extension_loaded('gd2')))) throw new akException('No imagick founded and no GD founded. Install one of this');
		
		// change state of using imagick to no
		if (!is_executable(self::imagickPath)) $this->usingImagick = false;
	}

	/**
	 * Fast init
	 * 
	 * @see this::__construct()
	 * @return object of akImage
	 */
	static function getInstance($path) {
		static $object;
		if (!$object) $object = new akImage($path);
		
		return $object;
	}

	/**
	 * Resize an image
	 * 
	 * @see this::__construct
	 * @param int $x - width
	 * @param int $y - height
	 * @param string $dst - destination file (if null then overwrite current)
	 * @param int $q - quality of destination image
	 * @param int $type - resize type (@see class properties, if null than no proportions)
	 * @param bool $zoom - zoom in destination image or not
	 * @return bool
	 * 
	 * @throws akException
	 */
	public function resize($x, $y, $dst = null, $q = 100, $type = null, $zoom = false) {
		if (!$this->usingImagick && !function_exists('imagecopyresized')) throw new akException('No suitable function found and imagick is not installed');
		if (($type < 1 || $type > 3) && $type !== null) throw new akException('This type not found');
		// if no destination, then changes will overwrite source file
		if (!$dst) $dst = $this->srcPath;
		
		if ($this->usingImagick) {
			// ignore proportions - default
			$size = sprintf('%ux%u!', $x, $y);
			
			if ($type !== null) {
				switch ($type) {
					case self::resizeByX:
						$size = $x;
						break;
					case self::resizeByY:
						$size = 'x' . $y;
						break;
					case self::resizeByXY:
						$size = sprintf('%ux%u', $x, $y);
						break;
				}
			}
			
			// try resize
			if (akExec::getInstance()->quickStart(sprintf('%s -quality %u -resize %s %s %s', self::imagickPath, $q, $size, $this->srcPath, $dst))) {
				return true;
			}
		} else {
			$image = $this->gdOpen($this->srcPath);
			$newX = $x;
			$newY = $y;
			// proportions
			if ($type !== null) {
				switch ($type) {
					case self::resizeByX:
						$scale = (imagesx($image) / $x);
						break;
					case self::resizeByY:
						$scale = (imagesx($image) / $y);
						break;
					case self::resizeByXY:
						$scale = (imagesx($image) > imagesy($image) ? (imagesy($image) / $y) : (imagesx($image) / $x));
						break;
				}
				
				// lost of quality maybe
				if ($scale > 1 || $zoom) {
					$newX = round($newX / $scale);
					$newY = round($newY / $scale);
				} else {
					return $this->gdSave($image, $dst, $q);
				}
			}
			$resizedImage = imagecreatetruecolor($newX, $newY);
			// png or gif -> add alpha channel
			if (in_array($this->extensionCheck($dst), array('png', 'gif'))) {
				imagealphablending($resizedImage, false);
				imagesavealpha($resizedImage,true);
				$transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
				imagefill($resizedImage, 0, 0, $transparent);
			}
			// resize
			if (imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newX, $newY, imagesx($image), imagesy($image))) {
				return $this->gdSave($resizedImage, $dst, $q);
			}
		}
		return false;
	}

	/**
	 * Rotate an image
	 * For imagick I need to do 360-$angle,
	 * and then result of output will be the same as using GD library
	 * 
	 * @param int $angle - angle rotate to
	 * @param string $dst - destination file (if null then overwrite current)
	 * @param int $q - quality of destination image
	 * @return bool
	 * 
	 * @throws akException
	 */
	public function rotate($angle, $dst = null, $q = 100) {
		if (!$this->usingImagick && !function_exists('imagerotate')) throw new akException('No suitable function found and imagick is not installed');
		// if no destination, then changes will overwrite source file
		if (!$dst) $dst = $this->srcPath;
		
		if ($this->usingImagick) {
			if (akExec::getInstance()->quickStart(sprintf('%s -quality %u -rotate %u %s %s', self::imagickPath, $q, 360-$angle, $this->srcPath, $dst))) {
				return true;
			}
		} else {
			$image = $this->gdOpen($this->srcPath);
			$image = imagerotate($image, $angle, 0);
			return $this->gdSave($image, $dst, $q);
		}
		return false;
	}

	/**
	 * Open image (detect write type)
	 * 
	 * @see this::extensionCheck()
	 * @param string $path - path to image
	 * @return resource
	 */
	protected function gdOpen($path) {
		$ext = $this->extensionCheck($path);
		return call_user_func_array('imagecreatefrom' . $ext, array($path));
	}

	/**
	 * Save image (detect write type)
	 * 
	 * @see this::extensionCheck()
	 * @param resource $image - image resource
	 * @param string $dst - path to destination image
	 * @param int $q - quality of destination image
	 * @return bool
	 */
	protected function gdSave($image, $dst, $q = 100) {
		$ext = $this->extensionCheck($dst);
		return call_user_func_array('image' . $ext, array($image, $dst, $q));
	}

	/**
	 * Check is extension supported or not
	 * 
	 * @param string $path - path to image
	 * @return string (ext on success)
	 * 
	 * @throws akException
	 */
	protected function extensionCheck($path) {
		$ext = akExec::getFileExt($path);
		// replace jpg to jpeg
		if ($ext == 'jpg') $ext = 'jpeg';
		
		if (in_array($ext, self::$avaliableExtensions)) {
			return $ext;
		}
		
		throw new akException('Such extensions is not supported yet');
	}
}
