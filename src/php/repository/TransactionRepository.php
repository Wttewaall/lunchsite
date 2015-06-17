<?php

namespace Lunchpot;

/**
 * Class TransactionRepository
 * @package Lunchpot
 * @author Bart Wttewaall <b.wttewaall@recognize.nl>
 */
class TransactionRepository extends Repository {
	
	public function create($account_id, $counterparty_account_id, $transaction_type_id, $transaction_status_id, $amount, $description, $date = null) {
		if (is_null($date)) $date = $this->now();
		
		// validate and sanitize input
		$values = array(
			$this->sqlValue($account_id),
			$this->sqlValue($counterparty_account_id),
			$this->sqlValue($transaction_type_id),
			$this->sqlValue($transaction_status_id),
			$this->sqlValue($amount * 100),
			$this->sqlValue($description),
			$this->sqlValue($date)
		);
		
		$values = join(', ', $values);
		
		$sql = "INSERT INTO transactions (
				`fk_account_id`,
				`fk_counterparty_account_id`,
				`fk_transaction_type`,
				`fk_transaction_status`,
				`amount`,
				`description`,
				`date`
			) VALUES ( $values );";
		
		return $this->connection->query($sql);
		
		/*if ($result) {
			return array('id' => $this->getLastInsertedTransactionId());
		}*/
	}
	
	public function read($id) {
		$sql = "SELECT
			trans.id,
			trans.date,
			TIMESTAMPDIFF(SECOND, TIMESTAMP(trans.date), CURRENT_TIMESTAMP) * 1000 AS time_diff,
			trans.amount,
			tstatus.code AS `status`,
			ttype.code AS `type`,
			IFNULL(trans.description, '') AS description
		
		FROM transactions trans
		LEFT JOIN transaction_status tstatus ON tstatus.id = trans.fk_transaction_status
		LEFT JOIN transaction_type ttype ON ttype.id = trans.fk_transaction_type

		WHERE trans.id = $id
		
		# for debugging purposes
		AND NOT trans.hidden

		ORDER BY trans.date DESC ";
		
		return $this->connection->get_results($sql);
	}
	
	public function getAllDetailed() {
		$sql = "SELECT
			trans.id,
			trans.date,
			TIMESTAMPDIFF(SECOND, TIMESTAMP(trans.date), CURRENT_TIMESTAMP) * 1000 AS time_diff,
			
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
		
		# for debugging purposes
		WHERE NOT trans.hidden

		ORDER BY trans.date DESC ";
		
		return $this->connection->get_results($sql);
	}
	
	public function update($id, array $fieldset) {
		// combine all fields
		$sqlFields = $this->prepareFields($fieldset);
		
		$sql = "UPDATE transactions transaction
			SET $sqlFields
			WHERE transaction.id = $id";
		
		return $this->connection->query($sql);
	}
	
	public function delete($id) {
		$sql = "DELETE FROM transactions
			WHERE id = $id
			LIMIT 1";
			
		return $this->connection->query($sql);
	}
	
	public function getLastInsertedTransactionId() {
		$sql = "SELECT trans.id
			FROM transactions trans
			ORDER BY id DESC
			LIMIT 1;";
		
		return $this->connection->get_var($sql);
	}
	
	public function getTransactionById($id) {
		$sql = "SELECT trans.id
			FROM transactions trans
			ORDER BY id DESC
			LIMIT 1;";
		
		return $this->connection->get_var($sql);
	}
	
	// ---- enums ----
	
	public function getTransactionTypes() {
		$sql = "SELECT * FROM transaction_type";
		return $this->connection->get_results($sql);
	}
	
	public function getTransactionStatusByCode($code) {
		$sql = "SELECT * FROM transaction_status
			WHERE transaction_status.code = '$code'";
		
		return $this->connection->get_row($sql);
	}
	
}

?>