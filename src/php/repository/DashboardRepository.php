<?php

namespace Lunchpot;

/**
 * Class DashboardRepository
 * @package Lunchpot
 * @author Bart Wttewaall <b.wttewaall@recognize.nl>
 */
class DashboardRepository extends Repository {
	
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
		
		return $this->connection->get_row($sql);
	}
	
}

?>