<?php

namespace Lunchpot;

use \ezSQLcore;

/**
 * Class Repository
 * @package Lunchpot
 * @author Bart Wttewaall <b.wttewaall@recognize.nl>
 */
class Repository {
	
	/**
	 * @var \ezSQLcore $connection
	 */
	protected $connection;
	
	function __construct(ezSQLcore $connection) {
		$this->connection = $connection;
	}
	
	public function now() {
		$date = new \DateTime();
		return $date->format(DateTime::ISO8601);
	}
	
	public function prepareFields($fieldset) {
		$fields = array();
		foreach ($fieldset as $key => $value) {
			$fields[] = '`'.$key.'` = '.$this->sqlValue($value);
		}
		return join(', ', $fields);
	}
	
	public function sqlValue($value) {
		$result = $value;
		if (is_string($value)) $result = "'".$value."'";
		if (is_bool($value)) $result = ($value == true) ? 1 : 0;
		if (is_null($value)) $result = 'NULL';
		return $result;
	}
	
}

?>