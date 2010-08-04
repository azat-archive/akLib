<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akCaptcha - CAPTCHA system
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @link http://php.net/gd
 * @see akException
 */

require_once 'sys/akException.class.php';

class akCaptcha {
	/**
	 * Session save key
	 */
	const sessionKey = 'akCaptcha';
	/**
	 * Minimum captcha length
	 * 
	 * @var int
	 */
	public $minLength = 5;
	/**
	 * Maximum captcha length
	 * 
	 * @var int
	 */
	public $maxLength = 6;
	/**
	 * Maximum numbers of session captcha
	 * 
	 * @var int
	 */
	public $maxNum = 5;
	/**
	 * Width of image
	 * 
	 * @var int
	 */
	public $width = 120;
	/**
	 * Height of image
	 * 
	 * @var int
	 */
	public $height = 30;
	/**
	 * TTF Font path
	 * 
	 * @var string
	 */
	public $fontPath = 'DejaVuSans.ttf';
	/**
	 * TTF Font size
	 * 
	 * @var int
	 */
	public $fontSize = 15;
	/**
	 * Rewrite old captcha
	 * If max captcha session num is achieved
	 * 
	 * @var bool
	 */
	public $rewriteOld = true;
	/**
	 * Case sensetive check for captcha exists
	 * 
	 * @see this::exists()
	 * @var bool
	 */
	public $caseSensetive = false;
	/**
	 * List with captcha's
	 * 
	 * @var link
	 */
	protected $list;


	/**
	 * Constructor
	 * 
	 * @see this::validate()
	 * @return void
	 */
	public function __construct($minLength = null, $maxLength = null, $maxNum = null) {
		// properties
		if ($minLength) $this->minLength = (int)$minLength;
		if ($maxLength) $this->maxLength = (int)$maxLength;
		if ($maxNum) $this->maxNum = (int)$maxNum;
		
		$this->validate();
		
		// create link to session captcha's list
		if (!isset($_SESSION[self::sessionKey]) || !is_array($_SESSION[self::sessionKey])) $_SESSION[self::sessionKey] = array();
		$this->list = &$_SESSION[self::sessionKey];
	}

	/**
	 * Fast init
	 * 
	 * @see this::__construct()
	 * @return object of akCaptcha
	 */
	static function getInstance($minLength = null, $maxLength = null, $maxNum = null) {
		static $object;
		if (!$object) $object = new akCaptcha($minLength, $maxLength, $maxNum);
		
		return $object;
	}

	/**
	 * Generate random string
	 * Only big leters
	 * 
	 * @return string
	 */
	protected function randomString() {
		$to = 0;
		while ($to == 0) {
			$to = rand($this->minLength, $this->maxLength);
		}
		
		$string = '';
		for ($i = 0; $i < $to; $i++) {
			if (rand(1, 2) % 2 == 1) $string .= rand(1, 9);
			else $string .= chr(rand(65, 90));
		}
		return $string;
	}

	/**
	 * Validate class properties
	 * 
	 * @return void
	 * 
	 * @throws akException
	 */
	protected function validate() {
		// font in current dir
		if (strpos($this->fontPath, '/') === false) {
			$this->fontPath = realpath(dirname(__FILE__)) . '/' . $this->fontPath;
		}
		
		// some errors
		if (!is_readable($this->fontPath)) throw new akException('Font path is not exists or not readable');
		if ($this->maxLength <= 0 || $this->maxLength >= 30) throw new akException('Error captcha size');
		if ($this->maxNum < 0) throw new akException('Max numbers of captcha must be > 0');
		if (!$this->width) throw new akException('No width isset');
		if (!$this->height) throw new akException('No height isset');
		if (!function_exists('imagepng')) throw new akException('No GD is installed');
	}

	/**
	 * Remember captcha to session
	 * And echo an image
	 * 
	 * @see this::validate()
	 * @return void
	 * 
	 * @throws akException
	 */
	public function set() {
		$value = $this->randomString();
		
		// check maximnun captcha's session
		if (count($this->list) > $this->maxLength) {
			if (!$this->rewriteOld) throw new akException('Max captchat`s session nums');
			else array_shift($this->list);
		}
		
		// size of one symbol
		$symbolSize = ($this->width / strlen($value));
		// errors
		$this->validate();
		if ($symbolSize < 10) throw new akException('Too small letters width, try less maxLength');
		// generate image
		$captcha = imagecreatetruecolor($this->width, $this->height);
		// colors
		$white = imagecolorallocate($captcha, 255, 255, 255);
		$grey = imagecolorallocate($captcha, 128, 128, 128);
		$black = imagecolorallocate($captcha, 0, 0, 0);
		// background
		imagefill($captcha, 0, 0, $white);
		// add string to image
		$margin = 0;
		
		// count padding from first letter
		$size = imagettfbbox($this->fontSize, 51, $this->fontPath, $value[0]);
		$marginX = ((abs($size[0] - $size[2]) + abs($size[6] - $size[4])) / 2);
		// write text
		for ($i = 0, $margin = $marginX; $i < strlen($value); $i++, $margin += $symbolSize) {
			// cat symbol with random angel
			imagettftext($captcha, $this->fontSize, (rand(0, 50)+1), $margin, $this->height, $grey, $this->fontPath, $value[$i]);
		}
		// add to captcha's list
		$this->list[] = $value;
		
		// echo image
		header('Content-type: image/png');
		imagepng($captcha);
		
	}

	/**
	 * Check is such captcha exists
	 * 
	 * @see this::caseSensetive
	 * @param string $value - value of captcha
	 * @return bool (true - exists, otherwise - false)
	 */
	public function exists($value) {
		if ($this->caseSensetive) {
			return in_array($value, $this->list);
		} else {
			return in_array(mb_strtolower($value), array_map('mb_strtolower', $this->list));
		}
	}

	/**
	 * Delete one captcha from list
	 * 
	 * @param string $value - value of captcha
	 * @return bool (true - deleted, otherwise - false (i.e. if not found such captcha))
	 */
	public function delete($value) {
		$key = array_search($value, $this->list);
		if ($key !== false) {
			unset($this->list[$key]);
			return true;
		}
		
		// not found
		return false;
	}

	/**
	 * Alias to this::set()
	 * 
	 * @see this::set()
	 */
	public function __toString() {
		$this->set();
		return '';
	}
}
