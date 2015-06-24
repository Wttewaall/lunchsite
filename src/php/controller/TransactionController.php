<?php

namespace Lunchpot;

class TransactionController {
	
	public function createAction($request, $response, $service, $app) {
		
		/*$is_valid = GUMP::is_valid($request->postParams(), array(
			'username' => 'required|alpha_numeric',
			'password' => 'required|max_len,100|min_len,6'
		));
		
		if (!$is_valid) throw new \Exception('invalid input: '.print_r($is_valid));*/
		
		$transactionStatus = $app->transactionRepository->getTransactionStatusByCode(TransactionStatus::AF);
		
		$result = $app->transactionRepository->create(
			$request->param('account_id'),
			$request->param('account_counterparty_id'),
			$request->param('transaction_type_id'),
			$transactionStatus->id,
			$request->param('transaction_amount'),
			$request->param('transaction_description'),
			$request->param('transaction_date')
		);
		
		$transaction = ($result) ? $app->transactionRepository->findLastInserted() : null;
		
		$response->json(array(
			'status' => $result,
			'transaction' => $transaction
		));
	}
	
	public function readAction($request, $response, $service, $app) {
		$transaction = $app->transactionRepository->find($request->id, true);
		return $response->json($transaction);
	}
	
}