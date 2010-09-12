<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akExec - Working with processes
 * 
 * This class can start sync process and async
 * ######################################################
 * But remeber that process that are runing after complete or script are terminated automaticly (in os *nix),
 * because parent of runing program (your script) are closed
 * ######################################################
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @link http://php.net/exec
 * 
 * @see akException
 */

require_once 'sys/akException.class.php';

class akExec {
	/**
	 * Needable descriptors
	 *
	 * @see To run process sync, STDERR must not be set, or set to file
	 *
	 * @var array
	 */
	public $descriptors = array(
	    // 1 => array('file', '/tmp/akExec.log', 'a'), // STDOUT, only for debug, I think
	    // 2 => array('pipe', 'r'), // STDERR
	);
	/**
	 * Pool of process, that are opens
	 *
	 * @var array
	 */
	protected $pool = array();
	/**
	 * Signal num for terminate
	 *
	 * @var int
	 */
	protected $terminateSignal = 15;
	/**
	 * Close open process on exit
	 * Default: true
	 * 
	 * @var bool
	 */
	public $terminateOnExit = true;


	/**
	 * Constructor
	 * 
	 * @see class properties
	 * @return void
	 * 
	 * @throws akException
	 */
	public function __construct() {}

	/**
	 * Fast init
	 * 
	 * @see this::__construct()
	 * @return object of akExec
	 */
	static function getInstance() {
		static $object;
		if (!$object) $object = new akExec();
		
		return $object;
	}
	
	/**
	 * Get file extension
	 * 
	 * @param string $path - path to file or hust name of file
	 * @return string
	 */
	static function getFileExt($path, $tolower = true) {
		if (preg_match('@\.([^./]+)$@is', $path, $ext)) {
			return ($tolower ? mb_strtolower($ext[1]) : $ext[1]);
		}
		return null;
	}

	/**
	 * Start a process
	 * No pipes just exec
	 * 
	 * @see this::start()
	 * @param string $path - command to run
	 * @param bool $escape - is need to escape args
	 * @return string
	 *
	 * @link http://php.net/exec
	 * 
	 * @throws akException
	 */
	public function quickStart($path, $escape = true) {
		$cmd = ($escape ? escapeshellcmd($path) : $path);
		
		exec($cmd, $output, $returnVar);
		// success
		if ($returnVar === 0) {
			return join("\n", $output);
		}
		throw new akException('Can`t start process or error is occured');
	}

	/**
	 * Start a process
	 * This basicly is for sync start
	 * 
	 * @see this::quickStart()
	 * @param string $path - command to run
	 * @param bool $escape - is need to escape args
	 * @return array
	 *
	 * @link http://php.net/proc_open
	 * 
	 * @throws akException
	 */
	public function start($path, $escape = true) {
		$cmd = ($escape ? escapeshellcmd($path) : $path);
		
		$pipes = array();
		$process = proc_open($cmd, $this->descriptors, $pipes);
		// all fine
		if ($process && is_resource($process)) {
			$pool = array(
			    'handler' => $process,
			    'pipes' => $pipes,
			    'start_time' => time(),
			);
			$this->pool[] = $pool;
			
			// if we set to get errors from STDERR and there is something, then throwing an expetion
			if (isset($this->descriptors[2]) && stream_get_contents($pipes[2])) {
				throw new akException('Error occurred while createing a processs');
			}
			
			return $pool;
		}
		throw new akException('Can`t start process');
	}

	/**
	 * Terimante process
	 * If non defined $handler, then terminate all
	 *
	 * @param array $handler - array that returns by self::start();
	 * @link http://php.net/proc_terminate
	 * 
	 * @throws akException
	 */
	public function terminate($handler = null) {
		// no process, return null
		if (!count($this->pool)) return null;
		
		// if defined, what to terminate
		if ($handler) {
			// see if process are in pool
			$handler = array_search($handler, $this->pool);
			if ($handler === false) {
				return null;
			}
			
			// check if process exists, then kill it
			if ($this->isRuning($this->pool[$handler]['handler']) && !proc_terminate($this->pool[$handler]['handler'], $this->terminateSignal)) {
				throw new akException('Can`t terminate prosses');
			}
			unset($this->pool[$handler]);
		}
		// otherwise - terminate all
		else {
			foreach ($this->pool as $key => &$pool) {
				// check if process exists, then kill it
				if ($this->isRuning($pool) && !proc_terminate($pool['handler'], $this->terminateSignal)) {
					throw new akException('Can`t terminate prosses');
				}
				unset($this->pool[$key]);
			}
		}
		return true;
	}

	/**
	 * Close process
	 * If non defined $handler, then close all
	 *
	 * @param resource $handler
	 * @link http://php.net/proc_close
	 * 
	 * @throws akException
	 */
	public function close($handler = null) {
		// no process, return null
		if (!count($this->pool)) return null;

		// if defined, what to close
		if ($handler) {
			// see if process are in pool
			$handler = array_search($handler, $this->pool);
			if ($handler === false) {
				return null;
			}

			// check if process exists, then kill it
			if ($this->isRuning($this->pool[$handler]['handler']) && !proc_close($this->pool[$handler]['handler'])) {
				throw new akException('Can`t close prosses');
			}
			unset($this->pool[$handler]);
		}
		// otherwise - close all
		else {
			foreach ($this->pool as $key => &$pool) {
				// check if process exists, then kill it
				if ($this->isRuning($pool) && !proc_close($pool['handler'])) {
					throw new akException('Can`t close prosses');
				}
				unset($this->pool[$key]);
			}
		}
		return true;
	}

	/**
	 * Get all pool
	 *
	 * @return array
	 */
	public function pool() {
		return $this->pool;
	}

	/**
	 * Try to close process
	 * And than terminate
	 *
	 * @return void
	 */
	public function  __destruct() {
		if ($this->terminateOnExit) {
			$this->close();
			$this->terminate();
		}
	}

	/**
	 * Is runing?
	 * If non defined $handler,
	 * then return true if one of process from pool is runing
	 *
	 * @param resource $handler
	 * @return mixed (bool or null)
	 * @link http://php.net/proc_get_status
	 */
	public function isRuning($handler = null) {
		// no process, return null
		if (!count($this->pool)) return null;
		
		// if defined, what to close
		if ($handler) {
			// see if process are in pool
			$handler = array_search($handler, $this->pool);
			if ($handler === false) {
				return null;
			}
			$status = proc_get_status($this->pool[$handler]['handler']);
			if ($status['running']) return true;
		} else {
			foreach ($this->pool as $pool) {
				$status = proc_get_status($pool['handler']);
				if ($status['running']) return true;
			}
		}
		return false;
	}

	/**
	 * Assign data with $handler
	 * IF no handler, than assign data with all processes
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @param resource $handler
	 * @return mixed
	 */
	public function assignData($key, $value, $handler = null) {
		// no process, return null
		if (!count($this->pool)) return null;

		// if defined, what to close
		if ($handler) {
			// see if process are in pool
			$handler = array_search($handler, $this->pool);
			if ($handler === false) {
				return null;
			}
			$this->pool[$handler]['user_data'][$key] = $value;
			return $this->pool[$handler];
		} else {
			foreach ($this->pool as &$pool) {
				$pool['user_data'][$key] = $value;
			}
			return $this->pool;
		}
	}

	/**
	 * Get data that are assignet with self::assignData()
	 *
	 * @see this::assignData()
	 * @param mixed $key
	 * @param resource $handler
	 */
	public function getData($key, $handler) {
		// no process, return null
		if (!count($this->pool)) return null;
		
		$handler = array_search($handler, $this->pool);
		if ($handler === false) {
			return null;
		}
		return $this->pool[$handler]['user_data'][$key];
	}
}
