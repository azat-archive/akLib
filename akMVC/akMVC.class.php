<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akMVC - MVC dispatcher (Model Viewer Controller dispatcher)
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @see akException
 * @see akDispatcher
 */

require_once 'sys/akException.class.php';
require_once 'akDispatcher/akDispatcher.class.php';

class akMVC extends akDispatcher {
	/**
	 * Path with models
	 * Default: DOCUMENT_ROOT/models
	 * 
	 * @var string
	 */
	protected $pathModels;
	/**
	 * Path with views
	 * Default: DOCUMENT_ROOT/views
	 * 
	 * @var string
	 */
	protected $pathViews;
	/**
	 * Path with controllers
	 * Default: DOCUMENT_ROOT/controllers
	 * 
	 * @var string
	 */
	protected $pathControllers;

	/**
	 * Constructor
	 * 
	 * @see parent::__construct()
	 */
	public function __construct($delimiter = null, $requestQuery = null, $charset = null, $type = null, $additionalParamDelimiter = null) {
		parent::__construct($delimiter = null, $requestQuery = null, $charset = null, $type = null, $additionalParamDelimiter);
		
		$this->setPaths('models', 'views', 'controllers');
	}

	/**
	 * Fast init
	 * 
	 * @see parent::getInstance()
	 */
	static function getInstance($delimiter = null, $requestQuery = null, $charset = null, $type = null, $additionalParamDelimiter = null) {
		static $object;
		if (!$object) $object = new akMVC($delimiter, $requestQuery, $charset, $type, $additionalParamDelimiter);
		
		return $object;
	}

	/**
	 * Add event
	 * 
	 * If param $funcOrContent is a function,
	 * than it must not to write some thing to STDOUT,
	 * it must return text that it want to write to STDOUT
	 * 
	 * @param mixed $path - urls/paths
	 * @param string $controller - controller file
	 * @param string $funcOrContent - function to run, or file to require, or formated string
	 * @param string $method - "get" or "post" or "both", both - for both methods
	 * @param int $type - type (@see this::NOT_FINAL_ROUTE, this::FINAL_ROUTE)
	 * @return void
	 * 
	 * @link http://php.net/callback
	 * 
	 * @throws akException
	 */
	public function add($path, $controller, $funcOrContent, $method = 'get', $type = self::FINAL_ROUTE) {
		$method = mb_strtolower($method);
		if (!in_array($method, array('get', 'post', 'both'))) throw new akException('Method must be "get" or "post" or "both"');
		if (!in_array($type, array(self::FINAL_ROUTE, self::NOT_FINAL_ROUTE))) throw new akException('Not supported type');
		
		if (is_scalar($path)) $path = array($path);
		foreach ($path as &$p) {
			$this->events[] = array(
				'path' => $p,
				'controller' => $controller,
				'funcOrContent' => $funcOrContent,
				'method' => $method,
				'type' => (int)$type,
			);
		}
	}

	/**
	 * Run dispatcher
	 * And it send all params to function, or to formated string, but not to file (because it's of secure)
	 * 
	 * And it include a controller file
	 * 
	 * @see If no suitable events then throwing an exception
	 * 
	 * @see this::pathControllers
	 * @return bool (true on success)
	 * 
	 * @throws akException
	 */
	public function run() {
		if (count($this->events) <= 0) throw new akException('No events found');
		$this->sortEvents();
		
		// run by all events and detect needable
		foreach ($this->events as &$event) {
			// flush array
			$this->params = array();
			
			// founded
			if (($matches = $this->checkRoute($event))) {
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
				
				// require controller file
				if (is_readable($this->pathControllers . $event['controller'])) {
					require_once $this->pathControllers . $event['controller'];
				} else {
					throw new akException(sprintf('Controller "%s" is not readable or not exists (see %s::pathControllers)', $event['controller'], __CLASS__));
				}
				
				// check funcOrContent param
				if (is_array($event['funcOrContent']) && count($event['funcOrContent']) == 2 && is_string($event['funcOrContent'][0])) {
					$event['funcOrContent'] = array(
						new $event['funcOrContent'][0],
						$event['funcOrContent'][1],
					);
				}
				
				// not final route
				if ($event['type'] == akDispatcher::NOT_FINAL_ROUTE) {
					$this->params ? call_user_func_array($event['funcOrContent'], $this->params) : call_user_func($event['funcOrContent']);
					continue;
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
				
				if ($content && $this->functionReturnsContent) echo $content;
				return true;
			}
		}
		
		if (!$this->defaultCallback) throw new akException('No suitable events');
		
		$this->headers();
		$content = call_user_func($this->defaultCallback);
		if ($this->functionReturnsContent) echo $content;
		return true;
	}

	/**
	 * Return content
	 * And it extract vars that set by this::set()
	 * 
	 * @see this::pathViews
	 * @param string $path - path to include
	 * @param string $type - type (only text types are avaliable)
	 * @return string
	 * 
	 * @throws akException if view not exists
	 */
	public function content($path) {
		$view = $this->pathViews . $path;
		if (!is_readable($view)) throw new akException(sprintf('View "%s" is not readable or not exists! (see %s::pathViews)', $path, __CLASS__));
		
		$this->headers();
		extract($this->vars);
		
		ob_start();
		require $view;
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}

	/**
	 * Require model
	 * Models mustn`t write some thing to STDOUT
	 * 
	 * @see this::pathModels
	 * @param string $path - path to include
	 * @param bool $once - once requiring or not (default: yes)
	 * @return void
	 * 
	 * @throws akException if model is not exists
	 */
	public function requireModel($path, $once = true) {
		$model = $this->pathModels . $path;
		if (!is_readable($model)) throw new akException(sprintf('Model "%s" is not readable or not exists! (see %s::pathModels)', $path, __CLASS__));
		
		$once ? require_once $model : require $model;
	}

	/**
	 * Set paths
	 * 
	 * @param string $models - models path
	 * @param string $views - views path
	 * @param string $controllers - controllers path
	 * @return void
	 */
	public function setPaths($models = null, $views = null, $controllers = null) {
		if (!is_null($models)) {
			if (!is_dir($models)) $models = realpath(sprintf('%s/%s', $_SERVER['DOCUMENT_ROOT'], $models));
			if (mb_substr($models, -1) != '/') $models .= '/';
			
			if (is_dir($models)) $this->pathModels = $models;
			else throw new akException(sprintf('"%s" is not a dir', $models));
			
			if (!is_readable($this->pathModels)) {	
				throw new akException(sprintf('Path "%s" is not readable', $this->pathModels));
			}
		}
		if (!is_null($views)) {
			if (!is_dir($views)) $views = realpath(sprintf('%s/%s', $_SERVER['DOCUMENT_ROOT'], $views));
			if (mb_substr($views, -1) != '/') $views .= '/';
			
			if (is_dir($views)) $this->pathViews = $views;
			else throw new akException(sprintf('"%s" is not a dir', $views));
			
			if (!is_readable($this->pathViews)) {	
				throw new akException(sprintf('Path "%s" is not readable', $this->pathViews));
			}
		}
		if (!is_null($controllers)) {
			if (!is_dir($controllers)) $controllers = realpath(sprintf('%s/%s', $_SERVER['DOCUMENT_ROOT'], $controllers)) . '/';
			if (mb_substr($controllers, -1) != '/') $controllers .= '/';
			
			if (is_dir($controllers)) $this->pathControllers = $controllers;
			else throw new akException(sprintf('"%s" is not a dir', $controllers));
			
			if (!is_readable($this->pathControllers)) {
				throw new akException(sprintf('Path "%s" is not readable', $this->pathControllers));
			}
		}
	}
}
