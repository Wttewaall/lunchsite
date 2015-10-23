angular.module('lunchsite.transaction', [])
.value('TransactionModel', {
	
	currentOffset	: 0,
	defaultLimit	: 10,
	lastResult		: null
	
});

/*.factory('Transaction', function() {
	
	return {
		transactionInstance: function() {
			return new Transaction();
		};
	};
	
});*/