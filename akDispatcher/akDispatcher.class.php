<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akDispatcher - Path dispatcher system
 * 
 * ######################################################
 * The callback functions must not to write some thing to STDOUT,
 * it must return text that it want to write to STDOUT
 * ######################################################
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @link http://www.pcre.org/
 * @link http://php.net/pcre
 * 
 * @see akException
 */

require_once 'sys/akException.class.php';

class akDispatcher {
	/**
	 * Path delimiter
	 * It need only to quote them (to get write param)
	 * 
	 * @var char (string, wich length = 1)
	 */
	protected $delimiter = '/';
	/**
	 * Additional param delimiter
	 * It need only to quote them (to get write param)
	 * 
	 * @example /akDispatcher/test/:first_:second (if value of this propery is "_" than this will be work)
	 * @see /akDispatcher/example.php
	 * 
	 * @var char (string, wich length = 1)
	 */
	protected $additionalParamDelimiter = '_';
	/**
	 * List of events
	 * 
	 * @see this::add()
	 * @see this::pattern()
	 * @var array
	 */
	protected $events = array();
	/**
	 * Request HTTP method
	 * Lowercase
	 * 
	 * @var string
	 */
	protected $requestMethod;
	/**
	 * Request HTTP query
	 * 
	 * @var string
	 */
	protected $requestQuery;
	/**
	 * List of vars
	 * 
	 * @see this::setParam()
	 * @see this::getParam()
	 * @see this::deleteParam()
	 * @var array
	 */
	protected $vars = array();
	/**
	 * List of params
	 * 
	 * @see this::run()
	 * @var array
	 */
	protected $params = array();
	/**
	 * Calls before user defined funcOrContent
	 * 
	 * @var callback
	 */
	protected $beforeCallback;
	/**
	 * Calls after user defined funcOrContent
	 * IT NOT RUN ON DEFAULT CALLBACK
	 * 
	 * @var callback
	 */
	protected $afterCallback;
	/**
	 * Calls if not suitable events are founded
	 * IT NOT RUN ON DEFAULT CALLBACK
	 * 
	 * @var callback
	 */
	protected $defaultCallback;
	/**
	 * Calls on error
	 * 
	 * @var callback
	 */
	protected $errorCallback;
	/**
	 * Content charset
	 * To send right headers
	 * 
	 * @var string
	 */
	protected $charset = 'UTF-8';
	/**
	 * Type of content (only text type are avaliable)
	 * 
	 * @var string
	 */
	protected $type = 'html';


	/**
	 * Constructor
	 * 
	 * @see class properties
	 * @return void
	 * 
	 * @throws akException
	 */
	public function __construct($delimiter = null, $requestQuery = null, $charset = null, $type = null, $additionalParamDelimiter = null) {
		// delimiter, only 1 first char
		if ($delimiter) $this->delimiter = (string)$delimiter[0];
		
		$this->requestMethod = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] ? mb_strtolower($_SERVER['REQUEST_METHOD']) : 'get');
		$this->requestQuery = ($requestQuery ? $requestQuery : $_SERVER['REQUEST_URI']);
		if ($charset) $this->charset = $charset;
		if ($type) $this->type = $type;
		if ($additionalParamDelimiter) $this->additionalParamDelimiter = $additionalParamDelimiter;
		
		// errors
		if (!$this->delimiter) throw new akException('No path delimiter');
		if (!$this->requestQuery) throw new akException('No HTTP query');
		if (!$this->requestMethod) throw new akException('No HTTP method');
		if (!$this->charset) throw new akException('No content charset');
		if (!$this->type) throw new akException('No type or content are set');
	}

	/**
	 * Fast init
	 * 
	 * @see this::__construct()
	 * @return object of akDispatcher
	 */
	static function getInstance($delimiter = null, $requestQuery = null, $charset = null, $type = null, $additionalParamDelimiter = null) {
		static $object;
		if (!$object) $object = new akDispatcher($delimiter, $requestQuery, $charset, $type);
		
		return $object;
	}

	/**
	 * Trunsfer path to pattern
	 * 
	 * @param string $path - path
	 * @return string
	 * 
	 * @example "/main/:param" => /main/asd ("asd" will be remember) or /main/123 ("123" will be remember)
	 * 
	 * @throws akException
	 */
	protected function pattern($path) {
		$path = $this->quote($path, array($this->delimiter, $this->additionalParamDelimiter, '/'));
		
		// delimiter
		static $quotedDelimiter;
		if (!$quotedDelimiter) {
			$quotedDelimiter = $this->quote($this->delimiter . $this->additionalParamDelimiter, array('/', $this->delimiter, $this->additionalParamDelimiter));
		}
		
		$path = preg_replace(
			sprintf('/\\\\\:([^\:%s\\\]+)/is', $quotedDelimiter),
			sprintf('(?P<\1>[^\:%s]+)', $quotedDelimiter),
			$path
		);
		
		return sprintf('/^%s$/is', $path);
	}

	/**
	 * PCRE quote
	 * 
	 * @param string $unquotedString
	 * @param mixed $delimiters
	 * @return string
	 */
	protected function quote($unquotedString, $delimiters) {
		if (is_scalar($delimiters)) $delimiters = array($delimiters);
		elseif (is_array($delimiters)) $delimiters = array_unique($delimiters);
		
		$unquotedString = preg_quote($unquotedString);
		
		foreach ($delimiters as $delimiter) {
			// see if preg_quote is already quoted this symbol
			if (preg_quote($delimiter) == '\\' . $delimiter) continue;
			
			$unquotedString = str_replace($delimiter, '\\' . $delimiter, $unquotedString);
		}
		return $unquotedString;
	}

	/**
	 * Add event
	 * 
	 * If param $funcOrContent is a function,
	 * than it must not to write some thing to STDOUT,
	 * it must return text that it want to write to STDOUT
	 * 
	 * @param string $path - url/path
	 * @param string $funcOrContent - function to run, or file to require, or formated string
	 * @param string $method - "get" or "post"
	 * @return void
	 * 
	 * @link http://php.net/callback
	 * 
	 * @throws akException
	 */
	public function add($path, $funcOrContent, $method = 'get') {
		if (!in_array($method, array('get', 'post'))) throw new akException('Method must be "get" or "post"');
		
		$this->events[] = array(
			'path' => $path,
			'funcOrContent' => $funcOrContent,
			'method' => mb_strtolower($method),
		);
	}

	/**
	 * Run dispatcher
	 * And it send all params to function, or to formated string, but not to file (because it's of secure)
	 * 
	 * @see If no suitable events then throwing an exception
	 * @return bool (true on success)
	 * 
	 * @throws akException
	 */
	public function run() {
		if (count($this->events) <= 0) throw new akException('No events found');
		
		// run by all events and detect needable
		foreach ($this->events as &$event) {
			// founded
			if (preg_match($this->pattern($event['path']), $this->requestQuery, $matches) && $event['method'] == $this->requestMethod) {
				// delete numeric params
				// first delete than add,
				// because we need to call user func with only string keys
				foreach ($matches as $key => &$value) {
					if (is_numeric($key)) unset($matches[$key]);
				}
				// add string params to param list
				foreach ($matches as $key => &$value) {
					$this->params[$key] = $value;
				}
				
				$content = '';
				if ($this->beforeCallback) $content .= call_user_func($this->beforeCallback);
				// function or content
				if (is_callable($event['funcOrContent'])) {
					$this->headers();
					$content .= ($this->params ? call_user_func_array($event['funcOrContent'], $this->params) : call_user_func($event['funcOrContent']));
				} elseif (is_readable($event['funcOrContent'])) {
					$content .= $this->content($event['funcOrContent'], 'html');
				} else {
					$this->headers();
					$content .= vsprintf($event['funcOrContent'], $this->params);
				}
				if ($this->afterCallback) $content .= call_user_func($this->afterCallback);
				
				if ($content) echo $content;
				return true;
			}
		}
		
		if (!$this->defaultCallback) throw new akException('No suitable events');
		
		$this->headers();
		$content = call_user_func($this->defaultCallback);
		echo $content;
		return true;
	}

	/**
	 * Send headers if not send yet
	 * 
	 * @return void
	 */
	protected function headers() {
		if (!headers_sent() && $this->type) header(sprintf('Content-type: text/%s; charset=%s', $this->type, $this->charset));
	}

	/**
	 * Return content
	 * And it extract vars that set by this::set()
	 * 
	 * @param string $path - path to include
	 * @param string $type - type (only text types are avaliable)
	 * @return string
	 * 
	 * @throws akException if file not exists
	 */
	public function content($path) {
		if (!is_readable($path)) throw new akException(sprintf('File "%s" is not exists!', $path));
		
		$this->headers();
		extract($this->vars);
		
		ob_start();
		require $path;
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}

	/**
	 * Return content
	 * 
	 * @param string $callback - function to call
	 * @param string $params - params to function
	 * @param string $type - type (only text types are avaliable)
	 * @return string
	 * 
	 * @throws akException if file not exists
	 */
	public function call($callback, $params = null) {
		if (!is_callable($callback)) throw new akException(sprintf('Function "%s" is not callable!', $callback));
		
		$this->headers();
		
		ob_start();
		if ($params) {
			call_user_func_array($callback, $params);
		} else {
			call_user_func($callback);
		}
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}

	/**
	 * Set var
	 * 
	 * @param string $key - key
	 * @param string $value - value
	 * @return void
	 */
	public function set($key, $value) {
		$this->vars[$key] = $value;
	}

	/**
	 * Get var
	 * 
	 * @param string $key - key
	 * @return mixed (mixed - found, otherwise - false (i.e. if not found such var))
	 */
	public function get($key) {
		return (isset($this->vars[$key]) ? $this->vars[$key] : false);
	}

	/**
	 * Delete var
	 * 
	 * @param string $key - key
	 * @return bool (true - deleted, otherwise - false (i.e. if not found such var))
	 */
	public function delete($key) {
		$key = array_search($key, $this->vars);
		// founded
		if ($key !== false) {
			unset($this->vars[$key]);
			return true;
		}
		// not found
		return false;
	}

	/**
	 * Get param
	 * 
	 * @param string $key - key
	 * @return mixed (mixed - found, otherwise - false (i.e. if not found such var))
	 */
	public function getParam($key) {
		return (isset($this->params[$key]) ? $this->params[$key] : false);
	}

	/**
	 * Set callbacks
	 * 
	 * The callback functions must not to write some thing to STDOUT,
	 * it must return text that it want to write to STDOUT
	 * 
	 * @param callback $before - before callback
	 * @param callback $after - after callback
	 * @param callback $default - default callback
	 * @param callback $error - error callback
	 * @param bool $toAllErrors - bind call back to all errors (otherwise only to "E_ALL ^ E_NOTICE ^ E_WARNING")
	 * @return void
	 * 
	 * @throws akException if one of functions is not callable
	 */
	public function setCallbacks($before = null, $after = null, $default = null, $error = null, $toAllErrors = false) {
		if ($before) {
			if (!is_callable($before)) {
				throw new akException(sprintf('Can`t set after callback, because function "%s" is not callable', $before));
			}
			
			$this->beforeCallback = $before;
		}
		if ($after) {
			if (!is_callable($after)) {
				throw new akException(sprintf('Can`t set after callback, because function "%s" is not callable', $after));
			}
			
			$this->afterCallback = $after;
		}
		if ($default) {
			if (!is_callable($default)) {
				throw new akException(sprintf('Can`t set default callback, because function "%s" is not callable', $default));
			}
			
			$this->defaultCallback = $default;
		}
		if ($error) {
			if (!is_callable($error)) {
				throw new akException(sprintf('Can`t set error callback, because function "%s" is not callable', $error));
			}
			
			$this->errorCallback = $error;
			if ($toAllErrors) {
				set_error_handler($this->errorCallback);
			} else {
				set_error_handler($this->errorCallback, E_ALL ^ E_NOTICE ^ E_WARNING);
			}
			set_exception_handler($this->errorCallback);
		}
	}

	/**
	 * Set debug mode
	 * 
	 * @return void
	 */
	public function setDebug($debug = false) {
		if ($debug) {
			ini_set('display_errors', 1);
			error_reporting(E_ALL);
		} else {
			ini_set('display_errors', 0);
			error_reporting(E_ERROR | E_WARNING | E_PARSE);
		}
	}
}
