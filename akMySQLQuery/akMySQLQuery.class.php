<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akMySQLQuery - DB system for mysql
 * 
 * @link http://php.net/mysql
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @see akException
 */

require_once 'sys/akException.class.php';
require_once 'akLog/akLog.class.php';

class akMySQLQuery {
	/**
	 * Last result
	 * 
	 * @var resource
	 */
	public $result;
	/**
	 * User to connect to
	 * 
	 * @var string
	 */
	protected $user;
	/**
	 * Password connect with
	 * 
	 * @var string
	 */
	protected $password;
	/**
	 * Host/Server to connect to
	 * 
	 * @var string
	 */
	protected $server;
	/**
	 * Port to connect to
	 * 
	 * @var int
	 */
	protected $port;
	/**
	 * DB charset
	 * 
	 * @var string
	 */
	protected $charset;
	/**
	 * DB name
	 * 
	 * @var string
	 */
	protected $db;
	/**
	 * Debug (using akLog)
	 * 
	 * @var bool
	 */
	public $debug = true;


	/**
	 * Constructor
	 * 
	 * @see this::connect()
	 * @return void
	 */
	public function __construct($server = null, $port = null, $user = null, $password = null, $charset = null, $db = null) {
		$this->connect($server, $port, $user, $password, $charset, $db);
	}

	/**
	 * Fast init
	 * 
	 * @return object of akMySQLQuery
	 */
	static function getInstance($server = null, $port = null, $user = null, $password = null, $charset = null, $db = null) {
		static $object;
		if (!$object) $object = new akMySQLQuery($server, $port, $user, $password, $charset, $db);
		
		return $object;
	}

	/**
	 * Sprintf + mysql
	 * Sending all data from sprintf to mysql_query
	 * 
	 * @param string $query - строка запроса
	 * @param mixed $arg1[, $arg2][, $arg3][, $arg4 ... ] - args for sprintf
	 * @return mysql resource
	 * 
	 * @throws akException
	 */
	public function sprintf() {
		$args = func_get_args();
		$query = array_shift($args);
		
		if (!($query = vsprintf($query, $args))) {
			throw new akException('Not enough args for vsprintf');
			return $this;
		}
		return $this->query($query);
	}
	
	/**
	 * Fetch all data from resource of mysql
	 * Using mysql_fetch_assoc
	 * 
	 * @param resource $result - mysql resource
	 * @return mixed
	 * 
	 * @throws akException
	 */
	public function fetchAll($result) {
		if (!is_resource($result)) throw new akException('This is not valid resource');
		
		$data = mysql_fetch_assoc($result);
		while ($data) {
			$array[] = $data;
			$data = mysql_fetch_assoc($result);
		}
		return (isset($array) ? $array : null);
	}
	
	/**
	 * Fetch one row data from resource of mysql
	 * Using mysql_fetch_assoc
	 * 
	 * @param resource $result - mysql resource
	 * @return mixed
	 * 
	 * @throws akException
	 */
	public function fetch($result) {
		if (!is_resource($result)) throw new akException('This is not valid resource');
		
		return mysql_fetch_assoc($result);
	}
	
	/**
	 * Return num rows
	 * 
	 * @param resource $result - mysql resource
	 * @return mixed
	 * 
	 * @throws akException
	 */
	public function numRows($result) {
		if (!is_resource($result)) throw new akException('This is not valid resource');
		
		return mysql_num_rows($result);
	}

	/**
	 * Join arrays to 'id = 1, add = true'
	 * 
	 * @param array $array - array
	 * @param bool $escape - escape values or array or not (default: true)
	 * @return string
	 */
	public function join($array, $escape = true) {
		if (empty($array)) return false;
		
		$query = '';
		foreach ($array as $key => $value) {
			$query .= sprintf('%s = "%s", ', $key, ($escape ? $this->escape($value) : $value));
		}
		return mb_substr($query, 0, -2);
	}

	/**
	 * Join arrays to 'id = 1 AND add = true'
	 * 
	 * @param array $array - array
	 * @param bool $escape - escape values or array or not (default: true)
	 * @param string $before - insert before value
	 * @param string $after - insert after value
	 * @return string
	 */
	public function andJoin($array, $escape = true, $before = '"', $after = '"') {
		if (empty($array)) return false;
		
		$query = '';
		foreach ($array as $key => $value) {
			if (preg_match('@(?:\s+like|=|<|>|\(|in)@Uis', $key)) {
				$query .= sprintf('%s %s%s%s AND ', $key, $before, ($escape ? $this->escape($value) : $value), $after);
			} else {
				$query .= sprintf('%s = %s%s%s AND ', $key, $before, ($escape ? $this->escape($value) : $value), $after);
			}
		}
		return mb_substr($query, 0, -5);
	}

	/**
	 * Join arrays to 'id = 1 OR add = true'
	 * 
	 * @param array $array - array
	 * @param bool $escape - escape values or array or not (default: true)
	 * @param string $before - insert before value
	 * @param string $after - insert after value
	 * @return string
	 */
	public function orJoin($array, $escape = true, $before = '"', $after = '"') {
		if (empty($array)) return false;
		
		$query = '';
		foreach ($array as $key => $value) {
			if (preg_match('@(?:\s+like|=|<|>|\(|in)@Uis', $key)) {
				$query .= sprintf('%s %s%s%s OR ', $key, $before, ($escape ? $this->escape($value) : $value), $after);
			} else {
				$query .= sprintf('%s = %s%s%s OR ', $key, $before, ($escape ? $this->escape($value) : $value), $after);
			}
		}
		return mb_substr($query, 0, -4);
	}

	/**
	 * Escape string
	 * If server is not avaliable then escape using mysql_escape_string
	 * otherwise using mysql_real_escape_string
	 * 
	 * @param string $unescapedString - string to escape
	 * @param bool $usePing - use ping or not, try to restore connection
	 * @return string
	 */
	public function escape($unescapedString, $usePing = false) {
		if (!$unescapedString) return false;
		
		if ($this->link) {
			$result = mysql_real_escape_string($unescapedString, $this->link);
			if ($usePing) mysql_ping($this->link);
			$result = mysql_real_escape_string($unescapedString, $this->link);
		} else {
			$result = mysql_real_escape_string($unescapedString);
		}
		
		if (!$result) $result = mysql_escape_string($unescapedString);
		return $result;
	}

	/**
	 * Query
	 * 
	 * @param string $query - mysql query
	 * @return resource
	 * 
	 * @throws akException
	 */
	public function query($query) {
		if (!is_resource($this->link)) throw new akException('First init connection to MySQL server');
		
		$begin = microtime(true);
		$this->result = mysql_query($query, $this->link);
		akLog::getInstance(true, !$this->debug)::sadd(
			'Query execute: %s [%.2f]%s',
			$query, (microtime(true) - $begin), (!$this->result ? sprintf(' [%s]', mysql_error($this->link)) : null)
		);
		if (!$this->result) {
			throw new akException(mysql_error($this->link));
		}
		return $this->result;
	}

	/**
	 * Connect to MySQL Server
	 * 
	 * @see class properties
	 * @param bool $refreshConnect - refresh connect anyway
	 * @return object of akMySQLQuery
	 * 
	 * @throws akException
	 */
	public function connect($server = null, $port = null, $user = null, $password = null, $charset = null, $db = null, $refreshConnect = false) {
		if ($server) $this->server = $server;
		if ($port) $this->port = $port;
		if ($user) $this->user = $user;
		if ($password) $this->password = $password;
		if ($charset) $this->charset = $charset;
		if ($db) $this->db = $db;
		
		$link = mysql_connect($this->server . ($this->port ? ':' . $this->port : null), $this->user, $this->password, $refreshConnect);
		if (!$link) throw new akException(mysql_error());
		
		$this->link = $link;
		// errors
		if ($this->charset && !mysql_set_charset($this->charset, $this->link)) throw new akException(mysql_error($this->link));
		if (!mysql_select_db($this->db, $this->link)) throw new akException(mysql_error($this->link));
		
		return $this;
	}

	/**
	 * Re-connect to MySQL Server
	 * 
	 * @return object of akMySQLQuery
	 * 
	 * @throws akException
	 */
	public function reConnect($usePingOnly = false) {
		if (mysql_ping($this->link)) return $this;
		
		// trying to reconnect ...
		if (!$usePingOnly) {
			$this->connect(null, null, null, null, null, null, true);
			if (mysql_ping($this->link)) {
				return $this;
			}
		}
		
		// default - error
		throw new akException('Can`t restore connection (%s)', mysql_error($this->link));
	}

	/**
	 * Change DB
	 * 
	 * @return object of akMySQLQuery
	 * 
	 * @throws akException
	 */
	public function changeDB($db) {
		$this->db = $db;
		if (!mysql_select_db($db, $this->link)) {
			throw new akException(mysql_error($this->link));
		}
		
		return $this;
	}

	/**
	 * Close connection
	 * 
	 * @return bool
	 * 
	 * @throws akException
	 */
	public function close() {
		if (!mysql_close()) throw new akException(mysql_error($this->link));
		unset($this->link);
	}

	/**
	 * Return last insert id
	 * 
	 * @return int
	 * 
	 * @throws akException
	 */
	public function insertId() {
		if (!is_resource($this->link)) throw new akException('First init connection to MySQL server');
		
		return mysql_insert_id($this->link);
	}
}
