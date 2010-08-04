<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * akQuery - DB query compose system
 * 
 * @TODO add methods leftJoin, rightJoin and so on
 * @TODO add some checks (i.e. no set() method if query is delete())
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @see akException
 */

require_once 'sys/akException.class.php';

class akQuery {
	/**
	 * Current avaliable join types
	 * 
	 * @var array
	 * @see this::join
	 * 
	 * @link http://www.mysql.ru/docs/man/JOIN.html
	 */
	static $joinTypes = array(
		'"CROSS JOIN" table_reference',
		'"JOIN" table_reference',
		'"INNER JOIN" table_reference join_condition',
		'"STRAIGHT_JOIN" table_reference',
		'"LEFT OUTER JOIN" table_reference join_condition',
		'"LEFT JOIN" table_reference join_condition',
		'"LEFT OUTER JOIN" table_reference',
		'"LEFT JOIN" table_reference',
		'"NATURAL LEFT OUTER JOIN" table_reference',
		'"NATURAL LEFT JOIN" table_reference',
		'"NATURAL OUTER JOIN" table_reference',
		'"RIGHT OUTER JOIN" table_reference join_condition',
		'"RIGHT JOIN" table_reference join_condition',
		'"RIGHT OUTER JOIN" table_reference',
		'"RIGHT JOIN" table_reference',
		'"NATURAL RIGHT OUTER JOIN" table_reference',
		'"NATURAL RIGHT JOIN" table_reference',
		'"NATURAL JOIN" table_reference',
	);
	/**
	 * Current query string
	 * 
	 * @var string
	 */
	public $queryString;


	/**
	 * Select
	 * 
	 * @param string $fields - fields
	 * @return object of akQuery
	 */
	static function select($fields) {
		$object = new akQuery;
		var_dump(__CLASS__);
		
		$object->queryString = sprintf('SELECT %s ', $fields);
		return $object;
	}

	/**
	 * Insert
	 * 
	 * @param string $dst - destination
	 * @return object of akQuery
	 */
	static function insert($dst) {
		$object = new akQuery;
		
		$object->queryString = sprintf('INSERT INTO %s ', $dst);
		return $object;
	}

	/**
	 * Update
	 * 
	 * @param string $dst - destination
	 * @return object of akQuery
	 */
	static function update($dst) {
		$object = new akQuery;
		
		$object->queryString = sprintf('UPDATE %s ', $dst);
		return $object;
	}

	/**
	 * Delete
	 * 
	 * @return object of akQuery
	 */
	static function delete() {
		$object = new akQuery;
		
		$object->queryString = 'DELETE ';
		return $object;
	}

	/**
	 * From
	 * 
	 * @param string $dst - destination
	 * @return object of akQuery
	 */
	public function from($dst) {
		$this->queryString .= sprintf('FROM %s ', $dst);
		return $this;
	}

	/**
	 * Insert values
	 * 
	 * @param string $values - values
	 * @return object of akQuery
	 */
	public function values($values) {
		$this->queryString .= sprintf('%s', $values);
		return $this;
	}

	/**
	 * Update/insert values
	 * 
	 * @param string $values - values
	 * @return object of akQuery
	 */
	public function set($values) {
		$this->queryString .= sprintf('SET %s ', $values);
		return $this;
	}

	/**
	 * Join
	 * 
	 * @param string $dst - destination
	 * @param string $joinType - type of join (LEFT, RIGHT and so on)
	 * @return object of akQuery
	 * 
	 * @throws akException
	 */
	public function join($dst, $joinType = 'left', $haveConditions = true) {
		// if type is valid
		if (preg_match('/^[a-z ]+$/is', $joinType)) {
			$joinType = mb_strtoupper($joinType);
			
			// see by pattern 1 (some possible)
			foreach (self::$joinTypes as &$type) {
				if (preg_match(sprintf('/"%s( [a-z]+)" table_reference%s/is', $joinType, ($haveConditions ? ' join_condition' : null)), $type, $matches)) {
					$foundedType = $type;
				}
			}
			if (!isset($foundedType)) {
				// see by pattern 2
				foreach (self::$joinTypes as &$type) {
					if (preg_match(sprintf('/"([a-z]+ )%s table_reference"%s/is', $joinType, ($haveConditions ? ' join_condition' : null)), $type, $matches)) {
						$foundedType = $type;
					}
				}
			}
			if (!isset($foundedType)) {
				// see by pattern 3
				foreach (self::$joinTypes as &$type) {
					if (preg_match(sprintf('/"([a-z]+ )%s( [a-z]+) table_reference"%s/is', $joinType, ($haveConditions ? ' join_condition' : null)), $type, $matches)) {
						$foundedType = $type;
					}
				}
			}
			if (!isset($foundedType)) {
				// see by pattern 4 (less possible)
				foreach (self::$joinTypes as &$type) {
					if (preg_match(sprintf('/"(([a-z]+ )*)%s(([a-z]+ )*) table_reference"%s/is', $joinType, ($haveConditions ? ' join_condition' : null)), $type, $matches)) {
						$foundedType = $type;
					}
				}
			}
			
			if (isset($foundedType)) {
				$foundedType = preg_replace('/"(.+)"/is', '\1', $foundedType);
				$this->queryString .= sprintf('%s ', preg_replace('/table_reference/is', $dst, mb_strtoupper($foundedType)));
				return $this;
			}
		}
		
		throw new akException('Such JOIN type is not supported');
		return $this;
	}

	/**
	 * Join conditions
	 * 
	 * @param string $conditions - conditions
	 * @return object of akQuery
	 * 
	 * @throws akException
	 */
	public function on($conditions) {
		if (!preg_match('/join_condition $/is', $this->queryString)) {
			throw new akException('No condition avaliable for this type of JOIN');
			return $this;
		}
		
		$this->queryString = sprintf('%s ', preg_replace('/join_condition $/is', sprintf('(%s)', $conditions), $this->queryString));
		return $this;
	}

	/**
	 * Where conditions
	 * 
	 * @param string $conditions - conditions
	 * @return object of akQuery
	 */
	public function where($conditions) {
		$this->queryString .= sprintf('WHERE %s ', $conditions);
		return $this;
	}

	/**
	 * Having conditions
	 * 
	 * @param string $conditions - conditions
	 * @return object of akQuery
	 */
	public function having($conditions) {
		$this->queryString .= sprintf('HAVING %s ', $conditions);
		return $this;
	}

	/**
	 * Group by
	 * 
	 * @param string $conditions - conditions
	 * @return object of akQuery
	 */
	public function group($conditions) {
		$this->queryString .= sprintf('GROUP BY %s ', $conditions);
		return $this;
	}

	/**
	 * Order by
	 * 
	 * @param string $conditions - conditions
	 * @return object of akQuery
	 */
	public function order($conditions) {
		$this->queryString .= sprintf('ORDER BY %s ', $conditions);
		return $this;
	}

	/**
	 * Limit
	 * 
	 * @param int $offset - offset
	 * @param int $limit - limit
	 * @return object of akQuery
	 */
	public function limit($offset, $limit) {
		$this->queryString .= sprintf('LIMIT %u, %u', $offset, $limit);
		return $this;
	}

	/**
	 * Return compose query
	 * 
	 * @return string
	 */
	public function getComposeQuery() {
		return $this->queryString;
	}

	/**
	 * Alias to this::getComposeQuery()
	 * 
	 * @see this::getComposeQuery()
	 */
	public function __toString() {
		return $this->getComposeQuery();
	}
}
