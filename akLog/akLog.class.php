<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akLog - Log system
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @see akException
 */

require_once 'sys/akException.class.php';
require_once 'akEmptyLog.class.php';

class akLog {
	/**
	 * All items
	 * 
	 * array (0 => array('description', 'file', 'line'))
	 * 
	 * @var array
	 */
	protected $items = array();
	/**
	 * Register cat log to shutdown
	 * 
	 * @var bool
	 */
	public $registerAsShutdown;


	/**
	 * Contructor
	 * 
	 * @see class properties
	 * @return void
	 */
	public function __construct($registerAsShutdown = true) {
		$this->registerAsShutdown = $registerAsShutdown;
	}

	/**
	 * Destructor
	 * 
	 * @see class properties
	 * @return void
	 */
	public function __destruct() {
		if ($this->registerAsShutdown) $this->cat();
	}

	/**
	 * Init
	 * 
	 * @see class properties
	 * @param bool $empty - is only empty wrappers or not (default: false)
	 * @return object of akEmptyLog or akLog
	 */
	static function getInstance($registerAsShutdown = true, $empty = false) {
		static $object;
		if (!$object) {
			if ($empty) $object = new akEmptyLog;
			else $object = new akLog($registerAsShutdown);
		}
		
		return $object;
	}

	/**
	 * Add to log
	 * 
	 * @param string $text - text
	 * @return void
	 */
	public function add($text) {
		$trace = debug_backtrace();
		
		$this->items[] = array(
			$text, $trace[0]['file'], $trace[0]['line']
		);
	}

	/**
	 * Add to log
	 * 
	 * @param string $formatedText - formated string
	 * @param mixed $arg1 - 
	 * @param mixed $arg2 - args for formated string
	 * @param mixed $argN - 
	 * @return void
	 */
	public function sadd() {
		$trace = debug_backtrace();
		$args = func_get_args();
		$text = array_shift($args);
		
		$this->items[] = array(
			vsprintf($text, $args), $trace[0]['file'], $trace[0]['line']
		);
	}

	/**
	 * Flush log
	 * 
	 * @return void
	 */
	public function flush() {
		unset($this->items);
	}

	/**
	 * Return all log
	 * 
	 * @return array
	 */
	public function get() {
		return $this->items;
	}

	/**
	 * Cat log
	 * 
	 * @return void
	 */
	public function cat() {
		if (PHP_SAPI != 'cli') echo '<pre class="akLog">';
		foreach ($this->items as &$item) {
			printf('%100s [%20s:%u]' . "\n", $item[0], $item[1], $item[2]);
		}
		if (PHP_SAPI != 'cli') echo '</pre>';
	}
}
