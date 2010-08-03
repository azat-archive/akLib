<?

/**
 * akSysQuery - DB query compose system (Sys - means queries like ALTER or CREATE)
 * 
 * @TODO add all specifications and types
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 * 
 * @see akException
 */

require_once 'sys/akException.class.php';
require_once 'akQuery/akQuery.class.php';

class akSysQuery extends akQuery {
	/**
	 * Alter
	 * 
	 * @param string $table - table
	 * @param string $otherConditions - other conditions
	 * @return object of akSysQuery
	 */
	static function alter($table, $otherConditions) {
		$object = new akSysQuery;
		
		if (!$otherConditions) throw new akException('Other conditions must set');
		
		$object->queryString = sprintf('ALTER %s %s', $table, $otherConditions);
		return $object;
	}

	/**
	 * Create table
	 * 
	 * @param string $table - table
	 * @param string $otherConditions - other conditions
	 * @param bool $ifNotExists - if not exists condition
	 * @return object of akSysQuery
	 * 
	 * @throws akException
	 */
	static function createTable($table, $otherConditions, $ifNotExists = false) {
		$object = new akSysQuery;
		
		if (!$otherConditions) throw new akException('Other conditions must set');
		
		$object->queryString = sprintf('CREATE TABLE %s%s %s', (!$ifNotExists ? 'IF NOT EXISTS ' : null), $table, $otherConditions);
		return $object;
	}

	/**
	 * Create DB
	 * 
	 * @param string $db - db
	 * @param bool $ifNotExists - if not exists condition
	 * @return object of akSysQuery
	 */
	static function createDB($db, $ifNotExists = false) {
		$object = new akSysQuery;
		
		$object->queryString = sprintf('CREATE DATABASE %s%s ', (!$ifNotExists ? 'IF NOT EXISTS ' : null), $db);
		return $object;
	}

	/**
	 * Drop table
	 * 
	 * @param string $table - table
	 * @return object of akSysQuery
	 */
	static function dropTable($table) {
		$object = new akSysQuery;
		
		$object->queryString = sprintf('DROP TABLE %s', $table);
		return $object;
	}

	/**
	 * Drop DB
	 * 
	 * @param string $db - db
	 * @return object of akSysQuery
	 */
	static function dropDB($db) {
		$object = new akSysQuery;
		
		$object->queryString = sprintf('DROP DATABASE %s', $db);
		return $object;
	}

	/**
	 * Describe table
	 * 
	 * @param string $table - table
	 * @return object of akSysQuery
	 */
	static function describe($table) {
		$object = new akSysQuery;
		
		$object->queryString = sprintf('DESCRIBE %s', $table);
		return $object;
	}

	/**
	 * Show tables like
	 * 
	 * @param string $table - table
	 * @return object of akSysQuery
	 */
	static function showTablesLike($table) {
		$object = new akSysQuery;
		
		$object->queryString = sprintf('SHOW TABLES ', $table);
		return $object;
	}

	/**
	 * Show index from
	 * 
	 * @return object of akSysQuery
	 */
	static function showIndexFrom() {
		$object = new akSysQuery;
		
		$object->queryString = sprintf('SHOW INDEX ');
		return $object;
	}

	/**
	 * Show table status
	 * 
	 * @return object of akSysQuery
	 */
	static function showTableStatus() {
		$object = new akSysQuery;
		
		$object->queryString = sprintf('SHOW TABLE STATUS ');
		return $object;
	}
	
	/**
	 * Like conditions
	 * 
	 * @param string $conditions - conditions
	 * @return object of akSysQuery
	 */
	public function like($conditions) {
		$this->queryString .= sprintf('LIKE "%s" ', $conditions);
		return $this;
	}
}
