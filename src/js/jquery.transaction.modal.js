(function( $ ) {
	'use strict';
	
	var transactionModal = function(element, options) {
		
		var TransactionModal = {
			
			getTransaction: function(id) {
				var self = this;
				
				$.ajax({
					type: 'POST',
					url: '/transaction/read',
					data: {id: id},
					dataType: 'json',
					
					beforeSend: function() {
						// hide form and show loader
					},
					
					success: function(data, textStatus, jqXHR) {
						self.populateData(data);
					},
					
					error: function() {
						console.warn('error');
					},
					
					complete: function() {
						// show form and hide loader
					}
				});
				return this;
			},
			
			populateData: function(data) {
				var $scope = $('#transactionForm', $(element));
				
				$('select[name="account_id"]', $scope).val(data.acc_id);
				$('select[name="account_counterparty_id"]', $scope).val(data.cacc_id);
				$('input[name="transaction_amount"]', $scope).val(parseFloat(data.amount));
				$('input[name="transaction_type_id"][value="'+data.type_id+'"]', $scope).prop('checked', true);
				$('input[name="transaction_date"]', $scope).val(data.date);
				$('textarea[name="transaction_description"]', $scope).val(data.description);
				
				return this;
			},
			
			reset: function() {
				console.warn('TODO');
				return this;
			}
		};
		
		return TransactionModal;
	};
	
	// initializes and returns the TransactionModal plugin
	$.fn.transactionModal = function (options) {
        return this.each(function () {
            var $this = $(this);
            
            if (!$this.data('TransactionModal')) {
                // create a private copy of the defaults object
                options = $.extend(true, {}, $.fn.transactionModal.defaults, options);
                $this.data('TransactionModal', transactionModal($this, options));
            }
            
        }).data('TransactionModal');
    };
 
}( jQuery ));