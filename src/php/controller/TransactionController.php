<?php

namespace Lunchpot;

class TransactionController {
	
	public function createAction($request, $response, $service, $app) {
		$data = array(
			'accounts'			=> $app->accountRepository->getAccounts(),
			'transactionTypes'	=> $app->accountRepository->getTransactionTypes()
		);
		
		return $app->twig->render('transaction.html.twig', $data);
	}
	
}