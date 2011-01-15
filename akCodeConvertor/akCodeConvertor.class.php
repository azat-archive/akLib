<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * BETA
 * 
 * akCodeConvertor - Convert functions, methods, variables like this
 * @example function some_thing() => function someThing()
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @link http://php.net/get_defined_functions
 * @see akException
 */

require_once 'sys/akException.class.php';

class akCodeConvertor {
	/**
	 * List of functions wich not be replaced (@see get_defined_functions())
	 * 
	 * @var array
	 */
	protected $defaultFunctions;
	/**
	 * Additional list of functions wich not be replaced
	 * 
	 * @var array
	 */
	public $additionalFunctions;
	/**
	 * List of functions wich not be replaced
	 * 
	 * @var array
	 */
	protected $functions;
	/**
	 * List of classes wich not be replaced (@see get_defined_classes())
	 * 
	 * @var array
	 */
	protected $defaultClasses;
	/**
	 * Additional classes of functions wich not be replaced
	 * 
	 * @var array
	 */
	public $additionalClasses;
	/**
	 * List of classes wich not be replaced
	 * 
	 * @var array
	 */
	protected $classes;
	/**
	 * List of vars wich not be replaced (@see get_defined_vars())
	 * 
	 * @var array
	 */
	protected $defaultVars;
	/**
	 * Additional list of vars wich not be replaced
	 * 
	 * @var array
	 */
	public $additionalVars;
	/**
	 * List of vars wich not be replaced
	 * 
	 * @var array
	 */
	protected $vars;
	/**
	 * List of files
	 * 
	 * @var array
	 */
	public $files;
	/**
	 * Case sensitive
	 * 
	 * @var bool
	 */
	public $caseSensitive = false;
	/**
	 * Prefix for new files (of not prefix is - rewrite files)
	 * 
	 * @var string
	 */
	public $prefixForNewFiles = '.new';
	/**
	 * @link http://ru.php.net/manual/en/pcre.configuration.php#ini.pcre.backtrack-limit
	 * 
	 * @var int
	 */
	public $oldBacktraceLimit;
	/**
	 * @link http://ru.php.net/manual/en/pcre.configuration.php#ini.pcre.recursion-limit
	 * 
	 * @var int
	 */
	public $oldRecursionLimit;

	/**
	 * Init
	 * 
	 * @see class properties
	 * @return void
	 * @throws akException
	 */
	public function __construct($files, array $additionalFunctions = null, array $additionalClasses = null, array $additionalVars = null, $caseSensitive = null) {
		if (is_scalar($files) && $files) $files = array($files);
		$this->files = $files;
		if (!$this->files) throw new akException('No files are set');
		
		if ($caseSensitive) $this->caseSensitive = $caseSensitive;
		if ($additionalFunctions) $this->additionalFunctions = $additionalFunctions;
		if ($additionalClasses) $this->additionalClasses = $additionalClasses;
		if ($additionalVars) $this->additionalVars = $additionalVars;
		$this->defaultFunctions = get_defined_functions();
		$this->defaultFunctions = $this->defaultFunctions['internal'];
		$this->defaultClasses = get_declared_classes();
		$this->defaultVars = array('_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES', '_SESSION', 'argv', 'argc');
		
		$this->oldBacktraceLimit = ini_set('pcre.backtrack_limit', 10000000);
		$this->oldRecursionLimit = ini_set('pcre.recursion_limit', 10000000);
	}
	
	/**
	 * Dectructor
	 * 
	 * @return void
	 */
	public function __destruct() {
		ini_set('pcre.backtrack_limit', $this->oldBacktraceLimit);
		ini_set('pcre.recursion_limit', $this->oldRecursionLimit);
	}

	/**
	 * Fast init
	 * 
	 * @see this::__construct()
	 * @return object of akCodeConvertor
	 */
	static function getInstance($files, array $additionalFunctions = null, array $additionalClasses = null, array $additionalVars = null, $caseSensitive = null) {
		static $object;
		if (!$object) $object = new akCodeConvertor($files, $additionalFunctions, $additionalClasses, $additionalVars, $caseSensitive);
		
		return $object;
	}

	/**
	 * Run
	 * 
	 * @return bool
	 * @throws akException
	 */
	public function run() {
		if (!$this->files) throw new akException('No files are set');
		
		$this->functions = ($this->additionalFunctions ? array_merge($this->defaultFunctions, $this->additionalFunctions) : array_merge($this->defaultFunctions));
		$this->classes = ($this->additionalClasses ? array_merge($this->defaultClasses, $this->additionalClasses) : $this->defaultClasses);
		$this->vars = ($this->additionalVars ? array_merge($this->defaultVars, $this->additionalVars) : $this->defaultVars);
		
		if (!$this->caseSensitive) {
			$this->functions = array_map('mb_strtolower', $this->functions);
			$this->classes = array_map('mb_strtolower', $this->classes);
			$this->vars = array_map('mb_strtolower', $this->vars);
		}
		
		foreach ($this->files as &$file) {
			if (!file_exists($file)) throw new akException(sprintf('File: %s is not exist', $file));
			if (!is_readable($file)) throw new akException(sprintf('File: %s is not readable', $file));
			if (!is_writable($file)) throw new akException(sprintf('File: %s is not writable', $file));
			
			$content = file_get_contents($file);
			// nothing to do with empty files
			if (!$content) continue;
			
			$content = preg_replace_callback('@(?P<begin><\?|<\?php\s)(?P<content>.+)(?P<end>\?>|$)@Uis', array(&$this, 'replace'), $content);
			// save changes
			if (!file_put_contents($file . $this->prefixForNewFiles, $content)) {
				throw new akException(sprintf('Can`t write changes to file: %s', $file . $this->prefixForNewFiles));
			}
		}
		return true;
	}
	
	/**
	 * Replace / Convert a file
	 * 
	 * @return bool
	 */
	protected function replace($matches) {
		static $braces = '\{((?>[^{}]+)|(?R))*\}';
		
		$content = &$matches['content'];
		// replace functions
		/// @TODO not working right (if function is method - then this function doesn`t see what class, maybe thereis no need to replace this class)
		$content = preg_replace_callback(
			'@(?P<begin>(?:function\s*|))(?P<name>[^\=\!\@\s\(\);_\']+_[^\=\!\@\s\(\);\']+)(?P<end>\s*\()@is',
			array(&$this, 'replaceFunctionsCallback'),
			$content
		);
		// static methods
		$content = preg_replace_callback(
			'@(?P<begin>(?P<class>[^\=\!\@\s\(\);\']+)(?P<delimiter>\s*::\s*))(?P<name>[^\!\@\s\(\);\']+)@is',
			array(&$this, 'replaceStaticMethodsCallback'),
			$content
		);
		// class const (PCRE recursion)
		/// @TODO but only that constants that defined before functions
		preg_match_all(
			'@(?P<begin>class\s*(?P<class>[^\=\!\@\s\(\);\{\']+)[^\}]+?)((?P<replacement>(?P<replacementBegin>const\s*)(?P<name>[^\=\!\@\s\(\);_\']+_[^\=\!\@\s\(\);\']+)(?P<replacementEnd>.+?))|(?P>replacement))@is',
			$content,
			$m
		);
		$content = preg_replace_callback(
			'@(?P<begin>class\s*(?P<class>[^\=\!\@\s\(\);\{\']+)[^\}]+?)((?P<replacement>(?P<replacementBegin>const\s*)(?P<name>[^\=\!\@\s\(\);_\']+_[^\=\!\@\s\(\);\']+)(?P<replacementEnd>.+?))|(?P>replacement))@is',
			array(&$this, 'replaceClassConst'),
			$content
		);
		// methods
		$content = preg_replace_callback(
			'@(?P<begin>\$(?P<class>[^\=\!\@\s\(\);\']+)(?P<delimiter>\s*->\s*))(?P<name>[^\!\@\s\(\);\']+)@is',
			array(&$this, 'replaceMethodsCallback'),
			$content
		);
		// replace classes
		$content = preg_replace_callback(
			'@(?P<begin>(?:class|new|clone|extends|implements|interface)\s*)(?P<name>[^\!\@\s\(\);\{_\']+_[^\!\@\s\(\);\{\']+)@is',
			array(&$this, 'replaceClassesCallback'),
			$content
		);
		// replace vars
		$content = preg_replace_callback(
			'@\$(?P<name>[^\=\!\@\s\(\);\[\]]+)(?P<delimiter>\s*)(?P<key>\[[^\s\(\);]+\]|)@is',
			array(&$this, 'replaceVarsCallback'),
			$content
		);
		return $matches['begin'] . $content . $matches['end'];
	}

	/**
	 * Callback for $this::replace() -> preg_replace_callback()
	 * For functions
	 * 
	 * @protected (public because of call as callback)
	 */
	public function replaceFunctionsCallback($matches) {
		$name = (!$this->caseSensitive ? mb_strtolower($matches['name']) : $matches['name']);
		if (in_array($name, $this->functions)) return $matches['begin'] . $matches['name'] . $matches['end'];
		
		return $matches['begin'] . $this->nameReplace($matches['name']) . $matches['end'];
	}

	/**
	 * Callback for $this::replace() -> preg_replace_callback()
	 * For static methods
	 * 
	 * @protected (public because of call as callback)
	 */
	public function replaceStaticMethodsCallback($matches) {
		$class = (!$this->caseSensitive ? mb_strtolower($matches['class']) : $matches['class']);
		$name = (!$this->caseSensitive ? mb_strtolower($matches['name']) : $matches['name']);
		if (in_array($class, $this->classes) || in_array($name, $this->functions)) return $matches['begin'] . $matches['name'];
		
		return $this->nameReplace($matches['class']) . $matches['delimiter'] . $this->nameReplace($matches['name']);
	}

	/**
	 * Callback for $this::replace() -> preg_replace_callback()
	 * For class constants
	 * 
	 * @protected (public because of call as callback)
	 */
	public function replaceClassConst($matches) {
		$class = (!$this->caseSensitive ? mb_strtolower($matches['class']) : $matches['class']);
		$name = (!$this->caseSensitive ? mb_strtolower($matches['name']) : $matches['name']);
		if (in_array($class, $this->classes)) return $matches['begin'] . $matches['replacement'];
		
		return $matches['begin'] . $matches['replacementBegin'] . $this->nameReplace($matches['name']) . $matches['replacementEnd'];
	}

	/**
	 * Callback for $this::replace() -> preg_replace_callback()
	 * For methods
	 * 
	 * @protected (public because of call as callback)
	 */
	public function replaceMethodsCallback($matches) {
		$class = (!$this->caseSensitive ? mb_strtolower($matches['class']) : $matches['class']);
		$name = (!$this->caseSensitive ? mb_strtolower($matches['name']) : $matches['name']);
		if (in_array($class, $this->vars) || in_array($name, $this->functions)) return $matches['begin'] . $matches['name'];
		
		return '$' . $this->nameReplace($matches['class']) . $matches['delimiter'] . $this->nameReplace($matches['name']);
	}

	/**
	 * Callback for $this::replace() -> preg_replace_callback()
	 * For classes
	 * 
	 * @protected (public because of call as callback)
	 */
	public function replaceClassesCallback($matches) {
		$name = (!$this->caseSensitive ? mb_strtolower($matches['name']) : $matches['name']);
		if (in_array($name, $this->classes)) return $matches['begin'] . $matches['name'];
		
		return $matches['begin'] . $this->nameReplace($matches['name']);
	}

	/**
	 * Callback for $this::replace() -> preg_replace_callback()
	 * For vars
	 * 
	 * @protected (public because of call as callback)
	 */
	public function replaceVarsCallback($matches) {
		$name = (!$this->caseSensitive ? mb_strtolower($matches['name']) : $matches['name']);
		if (in_array($name, $this->vars) || in_array($matches['key'], $this->defaultVars)) return '$' . $matches['name'] . $matches['delimiter'] . $matches['key'];
		
		return '$' . $this->nameReplace($matches['name']) . $matches['delimiter'] . ($matches['key'] ? $this->nameReplace($matches['key']) : null);
	}

	/**
	 * For PCRE callbacks
	 * Replace "a_a" to "aA"
	 * 
	 * @return string
	 */
	protected function nameReplace($name) {
		return preg_replace_callback('@(_)([a-z])@Uis', array($this, 'mbStrToUpperCallback'), $name);
	}

	/**
	 * Callback alias for mb_strtoupper
	 * 
	 * @protected (public because of call as callback)
	 */
	public function mbStrToUpperCallback($matches) {
		return mb_strtoupper($matches[2]);
	}
}
