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
	 * Type of CAPTCHA
	 * 
	 * Digits and letters
	 * @default
	 */
	const digitsAndLetters = 1;
	/**
	 * Type of CAPTCHA
	 * 
	 * Only digits
	 */
	const onlyDigits = 2;
	/**
	 * Type of CAPTCHA
	 * 
	 * Only letters
	 */
	const onlyLetters = 3;
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
	 * Font color in RGB
	 * 
	 * @var array of int
	 */
	public $fontColor = array(128, 128, 128);
	/**
	 * BG Font color in RGB
	 * 
	 * @var array of int
	 */
	public $bgFontColor = array(255, 255, 255);
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
	 * @see this::delete()
	 * @var bool
	 */
	public $caseSensetive = false;
	/**
	 * Type of captcha
	 * 
	 * @see this::digitsAndLetters - default
	 * @see this::onlyDigits
	 * @see this::onlyLetters
	 * 
	 * @var int
	 */
	public $type;
	/**
	 * Number of noise
	 * 
	 * @var int
	 */
	public $noiseNumber = 2;
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
	public function __construct($minLength = null, $maxLength = null, $maxNum = null, $type = null) {
		// properties
		if ($minLength) $this->minLength = (int)$minLength;
		if ($maxLength) $this->maxLength = (int)$maxLength;
		if ($maxNum) $this->maxNum = (int)$maxNum;
		$this->type = ($type ? $type : self::digitsAndLetters);
		
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
	 * @see this::onlyDigits
	 * @see this::onlyLetters
	 * @see this::digitsAndLetters
	 * 
	 * @return string
	 */
	protected function randomString() {
		$to = 0;
		// not to have $to = 0
		while ($to == 0) {
			$to = rand($this->minLength, $this->maxLength);
		}
		
		$string = '';
		for ($i = 0; $i < $to; $i++) {
			switch ($this->type) {
				case self::onlyDigits:
					$string .= rand(1, 9);
					break;
				case self::onlyLetters:
					$string .= chr(rand(65, 90));
					break;
				default: // self::digitsAndLetters
					if (rand(1, 2) % 2 == 1) $string .= rand(1, 9);
					else $string .= chr(rand(65, 90));
					break;
			}
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
			$this->fontPath = realpath(__DIR__) . '/' . $this->fontPath;
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
		$fontColor = imagecolorallocate($captcha, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
		$bgColor = imagecolorallocate($captcha, $this->bgFontColor[0], $this->bgFontColor[1], $this->bgFontColor[2]);
		// background
		imagefill($captcha, 0, 0, $bgColor);
		// add string to image
		$margin = 0;
		
		// count padding from first letter
		$size = imagettfbbox($this->fontSize, 51, $this->fontPath, $value[0]);
		$marginX = ((abs($size[0] - $size[2]) + abs($size[6] - $size[4])) / 2);
		// write text
		for ($i = 0, $margin = $marginX; $i < strlen($value); $i++, $margin += $symbolSize) {
			// cat symbol with random angel
			imagettftext($captcha, $this->fontSize, (rand(0, 50)+1), $margin, $this->height, $fontColor, $this->fontPath, $value[$i]);
		}
		// add noise
		$this->addNoise($captcha, $fontColor);
		// add to captcha's list
		$this->list[] = $value;
		
		// echo image
		header('Content-type: image/png');
		imagepng($captcha);
		
	}

	/**
	 * Add noise
	 * 
	 * @param resource $captcha - captcha GD resource
	 * @param int $fontColor - font color
	 * @return void
	 */
	protected function addNoise($captcha, $fontColor) {
		for ($i = 0; $i < $this->noiseNumber; $i++) {
			$y0 = ($i % 2 == 0 ? round(rand(0, $this->height/6)) : round(rand($this->height / 6, $this->height / 1.1)));
			$y1 = ($i % 2 == 0 ? round($this->height / 1.1) : round($this->height / rand(8,9)));
			
			$this->imagelineThick($captcha, round(rand(0, $this->width / 6)), $y0, round($this->width - rand(0, $this->width / 5)), $y1, $fontColor, 2);
		}
	}

	/**
	 * this way it works well only for orthogonal lines
	 * imagesetthickness($image, $thick);
	 * return imageline($image, $x1, $y1, $x2, $y2, $color);
	 * 
	 * @link http://php.net/imageline
	 */
	protected function imagelineThick($image, $x1, $y1, $x2, $y2, $color, $thick = 1) {
		if ($thick == 1) {
			return imageline($image, $x1, $y1, $x2, $y2, $color);
		}
		$t = $thick / 2 - 0.5;
		if ($x1 == $x2 || $y1 == $y2) {
			return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
		}
		$k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
		$a = $t / sqrt(1 + pow($k, 2));
		$points = array(
			round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
			round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
			round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
			round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
		);
		imagefilledpolygon($image, $points, 4, $color);
		return imagepolygon($image, $points, 4, $color);
	}

	/**
	 * Check is such captcha exists
	 * If exists than return true and delete it from list of captcha's
	 * 
	 * @see this::caseSensetive
	 * @param string $value - value of captcha
	 * @return bool (true - exists, otherwise - false)
	 */
	public function exists($value) {
		if ($this->caseSensetive) {
			if (in_array($value, $this->list)) {
				$this->delete($value);
				return true;
			}
		} else {
			if (in_array(mb_strtolower($value), array_map('mb_strtolower', $this->list))) {
				$this->delete($value);
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Delete one captcha from list
	 * 
	 * @param string $value - value of captcha
	 * @return bool (true - deleted, otherwise - false (i.e. if not found such captcha))
	 */
	public function delete($value) {
		if (!$value) return false;
		
		// because of caseSensetive option wee need use foreach
		foreach ($this->list as $key => &$item) {
			if (($this->caseSensetive && $item == $value) || (mb_strtolower($item) == mb_strtolower($value))) {
				unset($this->list[$key]);
				return true;
			}
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
