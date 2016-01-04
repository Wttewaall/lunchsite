<?php

namespace Lunchpot;

/**
 * Class AccountRepository
 * @package Lunchpot
 * @author Bart Wttewaall <b.wttewaall@recognize.nl>
 */
class AccountRepository extends Repository {
	
	public function find($id, $ignoreDeleted = true) {
		$filter	= "WHERE account.id = $id";
		$order	= "";
		$limit	= "";
		
		$sql = $this->buildSQL($filter, $order, $limit, $ignoreDeleted);
		return $this->connection->get_row( $sql );
	}
	
	public function findAll($ignoreDeleted = true) {
		$filter	= "";
		$order	= "ORDER BY account.first_name ASC";
		$limit	= "";
		
		$sql = $this->buildSQL($filter, $order, $limit, $ignoreDeleted);
		return $this->connection->get_results( $sql );
	}
	
	public function findLastInserted($ignoreDeleted = true) {
		$filter	= "";
		$order	= "ORDER BY account.id DESC";
		$limit	= "LIMIT 1";
		
		$sql = $this->buildSQL($filter, $order, $limit, $ignoreDeleted);
		return $this->connection->get_row( $sql );
	}
	
	protected function buildSQL($filter, $order, $limit, $ignoreDeleted = true) {
		$filter	= is_string($filter) ? $filter : '';
		$order	= is_string($order)  ? $order  : '';
		$limit	= is_string($limit)  ? $limit  : '';
		
		$sql = "SELECT
				account.*,
				
				(SELECT acc_t.code
					FROM account_state state
					LEFT JOIN account_type acc_t ON acc_t.id = state.fk_type_id
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
				) AS code,
				
				(SELECT state.participation
					FROM account_state state
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
				) AS participation,

				(SELECT state.modified_date
					FROM account_state state
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
				) AS modified_date
			
			FROM accounts account";
		
		return join("\n", array($sql, $filter, $order)).';';
	}
	
	// ---- TODO: de rest opschonen ----
	
	public function getAccounts($ignoreDeleted = true) {
		$whereFilter = ($ignoreDeleted) ? " WHERE account.deleted_date IS NULL" : "";
		
		$sql = "SELECT
				account.*,
				
				(SELECT acc_t.code
					FROM account_state state
					LEFT JOIN account_type acc_t ON acc_t.id = state.fk_type_id
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
				) AS code,
				
				(SELECT state.participation
					FROM account_state state
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
				) AS participation,
				
				(SELECT state.modified_date
					FROM account_state state
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
				) AS modified_date
			
			FROM accounts account
			$whereFilter
			ORDER BY account.first_name ASC";
		
		return $this->connection->get_results($sql);
	}
	
	public function getUserAccounts() {
		$accounts = $this->getAccounts(true);
		
		$result = array();
		foreach($accounts as $account) {
			if (in_array(strtoupper($account->code), AccountType::$USER_TYPES)) {
				$result[] = $account;
			}
		}
		
		return $result;
	}
	
	public function getNonUserAccounts() {
		$accounts = $this->getAccounts(true);
		
		$result = array();
		foreach($accounts as $account) {
			if (in_array(strtoupper($account->code), AccountType::$USER_TYPES) == false) {
				$result[] = $account;
			}
		}
		
		return $result;
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
				account.image,
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
	
	public function update($id, array $fieldset) {
		// combine all fields
		$sqlFields = $this->prepareFields($fieldset);
		
		$sql = "UPDATE accounts account
			SET $sqlFields
			WHERE account.id = $id";
		
		return $this->connection->query($sql);
	}
	
	public function delete($id) {
		$sql = "DELETE FROM accounts
			WHERE id = $id
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
	
	// ---- enums ----
	
	public function getAccountTypes() {
		$sql = "SELECT * FROM account_type";
		return $this->connection->get_results($sql);
	}
	
}

?>