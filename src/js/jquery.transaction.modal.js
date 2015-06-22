(function( $ ) {
	'use strict';
	
	var transactionModal = function(element, options) {
		var modalProxy = {
			
			getTransactionData: function(id) {
				console.log('getting TransactionData ...');
				$.ajax({
					url: '/transaction/'+id,
					data: null,
					success: function(data, textStatus, jqXHR) {
						console.log(data);
					},
					dataType: 'json'
				});
				return this;
			},
			
			populateData: function(data) {
				var $scope = $(this);
				$('input[name="account_id"]', $scope).val(data.accountId);
				$('input[name="account_counterparty_id"]', $scope).val(data.accountCounterpartyId);
				$('input[name="transaction_amount"]', $scope).val(data.transactionAmount);
				$('input[name="transaction_type_id"][value="'+data.transactionTypeId+'"]', $scope).prop('checked', true);
				$('input[name="transaction_date"]', $scope).val(data.transactionDate);
				$('input[name="transaction_description"]', $scope).val(data.transactionDescription);
				return this;
			},
			
			reset: function() {
				console.warn('TODO');
				return this;
			}
		};
		
		return modalProxy;
	}
	
	$.fn.transactionModal = function (options) {
        return this.each(function () {
            var $this = $(this);
            if (!$this.data('TransactionModal')) {
                // create a private copy of the defaults object
                options = $.extend(true, {}, $.fn.transactionModal.defaults, options);
                $this.data('TransactionModal', transactionModal($this, options));
            }
        });
    };
 
}( jQuery ));