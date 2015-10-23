angular.module('lunchsite.transaction', [])

.service('TransactionService', [
	'$http', '$parameters',
	function($http, $parameters) {
	
	return {
		
		getList: function(offset, limit) {
			return $http.get('/api/transactions', {
				params	: { offset:offset, limit:limit },
				cache	: $parameters.httpCache
			});
		}
		
	};
}]);