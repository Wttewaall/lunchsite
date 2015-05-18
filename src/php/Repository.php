<?php

use Klein\App;

class Repository {
	
	protected $connection;
	
	function __construct() {
		//$this->connection = $app->connection;
	}
	
	public function getAccountQuery($accountId) {
		return "SELECT
			account.id,
			account.first_name,
			account.last_name,
			account.iban,
			account.created_date,
			state.modified_date,
			account.deleted_date,
			state.participation,
			account_type.code
		FROM account_state state
		LEFT JOIN accounts account ON account.id = state.fk_account_id
		LEFT JOIN account_type ON account_type.id = state.fk_type_id
		WHERE account.id = $accountId
		ORDER BY state.modified_date DESC
		LIMIT 1";
	}
	
}

?>