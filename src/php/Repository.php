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
	
	// ---- misc ----
	
	public function getUserTotals() {
		$sql = "SELECT
				DISTINCT account.id,
				CONCAT(account.first_name, ' ', account.last_name) AS name,
				(SELECT acc_t.code
					FROM account_state state
					LEFT JOIN account_type acc_t ON acc_t.id = state.fk_type_id
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
				) AS `type`,
				
				ROUND(
					(
						# Get the sum of all rounds
						(	SELECT SUM(rounds.amount)
							FROM monthly_rounds rounds
						)
						
						* # Multiply by the participation percentage
						IFNULL((
							SELECT state.participation / 100
								FROM account_state state
								WHERE state.fk_account_id = account.id
								ORDER BY state.modified_date DESC
								LIMIT 1
						), 0)
					
						- # Subtract the user's sum of transactions
						(	SELECT
								IFNULL(SUM(CASE
									WHEN trans.acc_id = account.id THEN IF(trans.`status` = 'AF', trans.amount, trans.amount * -1)
									WHEN trans.cacc_id = account.id THEN IF(trans.`status` = 'BIJ', trans.amount, trans.amount * -1)
									ELSE 0
								END), 0)
							FROM view_all_transactions trans
							WHERE trans.acc_id = account.id OR trans.cacc_id = account.id
						)
					)
				# Divide by 100 and round down to 2 decimals
				/100, 2) AS amount
				
			FROM accounts account
			LEFT JOIN account_state ON account_state.fk_account_id = account.id
			LEFT JOIN account_type ON account_type.id = account_state.fk_type_id

			WHERE account_type.code in ('EMPLOYEE', 'INTERN') AND account.deleted_date IS NULL

			ORDER BY amount DESC ";
			
		return $this->connection->get_results($sql);
	}
	
	public function getDetailedTransactionsList() {
		$sql = "SELECT
			trans.date,
			
			acc.id AS acc_id,
			CONCAT(acc.first_name, ' ', acc.last_name) AS acc_name,
			(SELECT acc_t.code
				FROM account_state state
				LEFT JOIN account_type acc_t ON acc_t.id = state.fk_type_id
				WHERE state.fk_account_id = acc.id
				ORDER BY state.modified_date DESC
				LIMIT 1
			) AS acc_type,
			
			cacc.id AS cacc_id,
			CONCAT(cacc.first_name, ' ', cacc.last_name) AS cacc_name,
			(SELECT cacc_t.code
				FROM account_state state
				LEFT JOIN account_type cacc_t ON cacc_t.id = state.fk_type_id
				WHERE state.fk_account_id = cacc.id
				ORDER BY state.modified_date DESC
				LIMIT 1
			) AS cacc_type,
			
			trans.amount,
			tstatus.code AS `status`,
			ttype.code AS `type`,
			IFNULL(trans.description, '') AS description
			
		FROM transactions trans
		LEFT JOIN accounts acc ON acc.id = trans.fk_account_id
		LEFT JOIN accounts cacc ON cacc.id = trans.fk_counterparty_account_id
		LEFT JOIN transaction_status tstatus ON tstatus.id = trans.fk_transaction_status
		LEFT JOIN transaction_type ttype ON ttype.id = trans.fk_transaction_type

		#WHERE NOT trans.hidden

		ORDER BY trans.date DESC ";
		
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
		throw new \Exception('Not implemented yet');
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
		
		$fields = array();
		foreach ($arguments as $key => $value) {
			$fields[] = $key.' = '.$this->sqlValue($value);
		}
		
		// combine all fields
		$sqlFields = $fields|join(', ');
		
		$sql = "UPDATE accounts account
			SET $sqlFields
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
		throw new \Exception('405 Method Not Allowed');
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
		throw new \Exception('Not implemented yet');
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
	
	// ---- protected methods (utils) ----
	
	protected function now() {
		$date = new \DateTime();
		return $date->format(DateTime::ISO8601);
	}
	
	protected function sqlValue($value) {
		$result = $value;
		if (is_string($value)) $result = "'".$value."'";
		if (is_bool($value)) $result = ($value == true) ? 1 : 0;
		if (is_null($value)) $result = 'NULL';
		return $result;
	}
	
}

?>