angular.module('LunchsiteApp')

.controller('NavigationCtrl', ['$scope', '$mdSidenav', function($scope, $mdSidenav) {
	
	$scope.toggleSidenav = function(menuId) {
		$mdSidenav(menuId).toggle();
	};
	
}])

.controller('TransactionCtrl', [
	'$scope', 'TransactionModel', 'TransactionService',
	function($scope, model, service) {
	
	$scope.loading = false;
	$scope.data = null;
	
	function getPaginatedList(offset, limit) {
		$scope.loading = true;
		
		service.getList(offset || model.currentOffset, limit || model.defaultLimit)
			.then(function(result) {
				$scope.data = model.lastResult = result.data;
			})
			.finally(function() {
				$scope.loading = false;
			});
	}
	
	function getMore() {
		console.log('getMore');
		getPaginatedList(model.currentOffset++);
	}

}]);