<?php

namespace Lunchpot;

/**
 * Class TransactionRepository
 * @package Lunchpot
 * @author Bart Wttewaall <b.wttewaall@recognize.nl>
 */
class TransactionRepository extends Repository {
	
	public function find($id, $detailed = false) {
		$filter	= "WHERE trans.id = $id AND NOT trans.hidden";
		$order	= "ORDER BY trans.date DESC";
		$limit	= 'LIMIT 1';
		
		$sql = $this->buildSQL($filter, $order, $limit, $detailed);
		return $this->connection->get_row( $sql );
	}
	
	public function findAll($detailed = false) {
		$filter	= "WHERE NOT trans.hidden";
		$order	= "ORDER BY trans.date DESC";
		$limit	= '';
		
		$sql = $this->buildSQL($filter, $order, $limit, $detailed);
		return $this->connection->get_results( $sql );
	}
	
	public function findLastInserted($detailed = false) {
		$filter	= "WHERE NOT trans.hidden";
		$order	= "ORDER BY trans.id DESC";
		$limit	= 'LIMIT 1';
		
		$sql = $this->buildSQL($filter, $order, $limit, $detailed);
		return $this->connection->get_row( $sql );
	}
	
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
	}
	
	public function update($id, array $fieldset) {
		
		if (isset($fieldset['amount'])) {
			$fieldset['amount'] = floatval($fieldset['amount']) * 100;
		}
		
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
	
	// ---- protected methods ----
	
	protected function buildSQL($filter, $order, $limit, $detailed = false) {
		$filter	= is_string($filter) ? $filter : '';
		$order	= is_string($order)  ? $order  : '';
		$limit	= is_string($limit)  ? $limit  : '';
		
		$defaultSelector = "SELECT
			trans.id,
			trans.date,
			TIMESTAMPDIFF(SECOND, TIMESTAMP(trans.date), CURRENT_TIMESTAMP) * 1000 AS time_diff,
			(trans.amount / 100) as `amount`,
			tstatus.code AS `status`,
			ttype.code AS `type`,
			IFNULL(trans.description, '') AS description
		
		FROM transactions trans
		LEFT JOIN transaction_status tstatus ON tstatus.id = trans.fk_transaction_status
		LEFT JOIN transaction_type ttype ON ttype.id = trans.fk_transaction_type";
		
		$detailedSelector = "SELECT
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
			
			(trans.amount / 100) as `amount`,
			tstatus.id AS `status_id`,
			tstatus.code AS `status`,
			ttype.id AS `type_id`,
			ttype.code AS `type`,
			IFNULL(trans.description, '') AS description
			
		FROM transactions trans
		LEFT JOIN accounts acc ON acc.id = trans.fk_account_id
		LEFT JOIN accounts cacc ON cacc.id = trans.fk_counterparty_account_id
		LEFT JOIN transaction_status tstatus ON tstatus.id = trans.fk_transaction_status
		LEFT JOIN transaction_type ttype ON ttype.id = trans.fk_transaction_type";
		
		
		$sql = ($detailed !== true) ? $defaultSelector : $detailedSelector;
		return join("\n", array($sql, $filter, $order)).';';
	}
	
}

?>