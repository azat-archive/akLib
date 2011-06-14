<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akColorOutput - color output for *nix CLI
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @link http://www.pcre.org/
 * @link http://php.net/pcre
 */

class akColorOutput {
	/**
	 * Color is bold
	 *
	 * @var bool
	 */
	protected $colorBold = false;
	/**
	 * Color
	 * @see self::COLOR_*
	 *
	 * @var int
	 */
	protected $color;
	/**
	 * Not windows OS
	 *
	 * @var bool
	 */
	protected $notWin = true;
	/**
	 * Red color
	 */
	const COLOR_RED	= 31;
	/**
	 * Green color
	 */
	const COLOR_GREEN = 32;
	/**
	 * Yellow color
	 */
	const COLOR_YELLOW= 33;
	/**
	 * Blue color
	 */
	const COLOR_BLUE 	= 34;
	/**
	 * Pink color
	 */
	const COLOR_PINK 	= 35;
	/**
	 * Teal color
	 */
	const COLOR_TEAL 	= 36;
	/**
	 * White color
	 */
	const COLOR_WHITE = 37;


	public function __construct() {
		if (stristr(PHP_OS, 'win') !== false) {
			$this->notWin = false;
		}
		$this->color = self::COLOR_GREEN;
	}

	/**
	 * Fast init
	 * 
	 * @return ColorOutput
	 */
	static public function getInstance() {
		static $o;
		if (!$o) {
			$o = new self;
		}
		return $o;
	}

	public function printf() {
		return printf(call_user_func_array(array(&$this, 'sprintf'), func_get_args()));
	}

	public function sprintf() {
		$str = call_user_func_array('sprintf', func_get_args());
		
		if ($this->notWin) {
			return sprintf("\033[%u;%um%s\033[0m", $this->colorBold, $this->color, $str);
		} else {
			return sprintf("%0.2f%%", $str);
		}
	}

	public function setColor($color) {
		$this->color = $color;
		return $this;
	}

	public function setBold($bold) {
		$this->colorBold = $bold;
		return $this;
	}
}
