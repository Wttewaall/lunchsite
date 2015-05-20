<?php

use \ezSQLcore;

class Repository {
	
	/** @var ezSQLcore $connection **/
	protected $connection;
	
	function __construct(ezSQLcore $connection) {
		$this->connection = $connection;
	}
	
	// ---- views ----
	
	public function getTransactions() {
		$sql = "SELECT * FROM view_all_transactions";
		return $this->connection->get_results($sql);
	}
	
	public function getUserTotals() {
		$sql = "SELECT * FROM view_total_for_users";
		return $this->connection->get_results($sql);
	}
	
	// ---- accounts ----
	
	public function getAccounts($ignoreDeleted = false) {
		$sql = "SELECT account.*
			FROM accounts account
			ORDER BY account.first_name ASC";
		
		$sql .= ($ignoreDeleted) ? " WHERE account.deleted_date IS NULL" : "";
		
		return $this->connection->get_results($sql);
	}
	
	public function getMutableAccounts() {
		$accounts = $this->getAccounts();
		
		return array_filter($accounts, function($account) {
			$state = $this->getRecentAccountState($account->id);
			return (!in_array($state->code, array('Supermarket')));
		});
	}
	
	public function getNonEmployeeAccounts() {
	}
	
	public function getLunchpotAccount() {
		$sql = "SELECT account.*
			FROM accounts account
			WHERE account.first_name = 'Lunch pot'";
		
		return $this->connection->get_row($sql);
	}
	
	public function getAccountById($account_id) {
		$sql = "SELECT
				account.id,
				account.first_name,
				account.last_name,
				account.account_name,
				account.iban,
				account.created_date,
				state.modified_date,
				account.deleted_date,
				state.participation,
				account_type.code
			FROM account_state state
			LEFT JOIN accounts account ON account.id = state.fk_account_id
			LEFT JOIN account_type ON account_type.id = state.fk_type_id
			WHERE account.id = $account_id
			ORDER BY state.modified_date DESC
			LIMIT 1";
		
		return $this->connection->get_row($sql);
	}
	
	public function createAccount($first_name, $last_name, $account_name, $iban, $type_id, $participation) {
		$now = $this->now();
		
		$sql = "INSERT INTO accounts (`first_name`, `last_name`, `account_name`, `iban`, `created_date`)
			VALUES ($first_name, $last_name, $account_name, $iban, $now)";
		$success = $this->connection->query($sql);
		
		$success2 = $this->createAccountState($type_id, $participation, $now);
		
		return ($success && $success2);
	}
	
	public function editAccount($account_id, array $arguments) {
		print('Not implemented yet');
		
		$sql = "UPDATE accounts account
			SET name = 'Justin'
			WHERE account.id = $account_id";
		
		$success = $this->connection->query($sql);
		
		// TODO: add new state if any of the state properties are changed
		
		return $success;
	}
	
	public function deleteAccount($account_id) {
		$sql = "DELETE FROM accounts
			WHERE id = $account_id
			LIMIT 1";
			
		return $this->connection->query($sql);
	}
	
	// ---- account state ----
	
	public function getAccountStates() {
		$sql = "SELECT * FROM account_state";
		return $this->connection->get_results($sql);
	}
	
	public function createAccountState($account_id, $type_id, $participation, $modified_date = null) {
		if (is_null($modified_date)) $modified_date = $this->now();
		
		$sql = "INSERT INTO account_state (`fk_account_id`, `fk_type_id`, `participation`, `modified_date`)
			VALUES ($account_id, $type_id, $participation, $modified_date)";
			
		return $this->connection->query($sql);
	}
	
	public function editAccountState($state_id, array $arguments) {
		throw new \Exception('Not implemented yet');
	}
	
	public function deleteAccountState($state_id) {
		throw new \Exception('Not allowed');
	}
	
	public function getRecentAccountState($account_id) {
		$sql = "SELECT
				state.*,
				account_type.code
			FROM account_state state
			LEFT JOIN account_type ON account_type.id = state.fk_type_id
			WHERE state.fk_account_id = $account_id
			ORDER BY state.modified_date DESC
			LIMIT 1";
			
		return $this->connection->get_row($sql);
	}
	
	public function getTotalCash() {
		$account_id = 1; // Lunch pot
		
		$sql = "SELECT
			IF(acc.id = $account_id, acc.first_name, cacc.first_name) as name,
			tt.code,
			SUM(
				IF(acc.id = $account_id, trans.amount * -1, trans.amount)
			) as amount
		FROM transactions trans
		LEFT JOIN transaction_type tt ON tt.id = trans.fk_transaction_type
		LEFT JOIN accounts acc ON acc.id = trans.fk_account_id
		LEFT JOIN accounts cacc ON cacc.id = trans.fk_counterparty_account_id
		WHERE (acc.id = $account_id OR cacc.id = $account_id) AND tt.code = 'CASH';";
		
		return $this->connection->get_row($sql);
	}
	
	/**
	 *	TODO fix the system: use the old pot for old depts and substract it from the new pot's total in further calculations
	 **/
	public function getTotalBank() {
		$account_id = 1; // Lunch pot
		
		$sql = "SELECT
			acc.first_name as acc_name,
			cacc.first_name as cacc_name,
			IF(acc.id = $account_id, acc.first_name, cacc.first_name) as name,
			tt.code,
			SUM(
				IF(acc.id = $account_id, trans.amount * -1, trans.amount)
			) as amount
		FROM transactions trans
		LEFT JOIN transaction_type tt ON tt.id = trans.fk_transaction_type
		LEFT JOIN accounts acc ON acc.id = trans.fk_account_id
		LEFT JOIN accounts cacc ON cacc.id = trans.fk_counterparty_account_id
		LEFT JOIN account_state cacc_state ON cacc_state.fk_account_id = cacc.id
		LEFT JOIN account_type cacc_type ON cacc_type.id = cacc_state.fk_type_id
		WHERE (acc.id = $account_id OR cacc.id = $account_id) AND tt.code = 'BANK' AND cacc_type.code NOT IN ('Employee', 'Intern')";
		
		$result = $this->connection->get_row($sql);
		$result->amount -= 4000; // Bart's donation to the old Pot
		return $result;
	}
	
	// ---- transactions ----
	
	public function createTransaction($account_id, $counterparty_account_id, $transaction_type, $transaction_status, $amount, $description, $date = null) {
		if (is_null($date)) $date = $this->now();
		
		$sql = "INSERT INTO transactions (`fk_account_id`, `fk_counterparty_account_id`, `fk_transaction_type`, `fk_transaction_status`, `amount`, `description`, `date`)
			VALUES ($account_id, $counterparty_account_id, $transaction_type, $transaction_status, $amount, $description, $date)";
		
		return $this->connection->query($sql);
	}
	
	public function editTransaction($transaction_id, array $arguments) {
		print('Not implemented yet');
	}
	
	// ---- enums ----
	
	public function getAccountTypes() {
		$sql = "SELECT * FROM account_type";
		return $this->connection->get_results($sql);
	}
	
	public function getTransactionTypes() {
		$sql = "SELECT * FROM transaction_type";
		return $this->connection->get_results($sql);
	}
	
	// ---- utils ----
	
	public function now() {
		$date = new \DateTime();
		return $date->format(DateTime::ISO8601);
	}
	
}

?>