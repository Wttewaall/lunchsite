<?php
	
	include_once "../ezSQL/shared/ez_sql_core.php";
	include_once "../ezSQL/pdo/ez_sql_pdo.php";
	
	$db = null;
	
	function getDB() {
		if (is_null($db)) $db = new ezSQL_pdo('mysql:host=127.0.0.1;port=3306;dbname=lunch', 'root', '');
		return $db;
	}
	
	print_r($_GET);

	
	/**
	 *  ezSQL demo for mySQL database
	 */
	function demo() {
		$db = $this->getDB();

		// Demo of getting a single variable from the db
		// (and using abstracted function sysdate)
		$current_time = $db->get_var("SELECT " . $db->sysdate());
		print "ezSQL demo for mySQL database run @ $current_time";

		// Print out last query and results..
		$db->debug();

		// Get list of tables from current database..
		$my_tables = $db->get_results("SHOW TABLES",ARRAY_N);

		// Print out last query and results..
		$db->debug();

		// Loop through each row of results..
		foreach ( $my_tables as $table ) {
			// Get results of DESC table..
			$db->get_results("DESC $table[0]");

			// Print out last query and results..
			$db->debug();
		}
	}

?>