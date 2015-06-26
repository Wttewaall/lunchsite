(function( $ ) {
	'use strict';
	
	var transactionModal = function(element, options) {
		
		var TransactionModal = {
			
			mode: 'create',
			
			getMode: function() {
				return this.mode;
			},
			
			setMode: function(mode) {
				this.mode = mode;
				
				var $title = $(element).find('.modal-title');
				if (mode == 'create') $title.text('Nieuwe transactie');
				if (mode == 'update') $title.text('Transactie aanpassen');
			},
			
			createTransaction: function() {
				var self = this;
				
				$.ajax({
					type: 'POST',
					url: '/transaction/create',
					data: self.getData(),
					dataType: 'json',
					
					success: function(data, textStatus, jqXHR) {
						console.log('success');
					},
					
					error: function() {
						console.warn('error');
					}
				});
				return this;
			},
			
			validateForm: function() {
				return true;
			},
			
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
						self.setData(data);
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
			
			setTransaction: function(data) {
				this.setData(data);
			},
			
			getData: function() {
				var $scope = $('#transactionForm', $(element));
				
				var data = {
					id: $('input[name="transaction_id"]', $scope).val(),
					account_id: $('select[name="account_id"] option:selected', $scope).val(),
					account_counterparty_id: $('select[name="account_counterparty_id"] option:selected', $scope).val(),
					transaction_amount: $('input[name="transaction_amount"]', $scope).val(),
					transaction_type_id: $('input[name="transaction_type_id"]', $scope).val(),
					transaction_date: $('input[name="transaction_date"]', $scope).val(),
					transaction_description: $('textarea[name="transaction_description"]', $scope).val()
				};
				
				// from NL to DateTime
				data.transaction_date = moment(data.transaction_date, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
				
				return data;
			},
			
			setData: function(data) {
				var $scope = $('#transactionForm', $(element));
				
				// from DateTime to NL
				data.date = moment(data.date, 'YYYY-MM-DD HH:mm:ss').format('DD-MM-YYYY HH:mm');
				
				console.log('data:', data);
				
				// set id
				$('input[name="transaction_id"]', $scope).val(data.id);
				$('select[name="account_id"] option[value="'+data.acc_id+'"]', $scope).prop('selected', true);
				$('select[name="account_counterparty_id"] option[value="'+data.cacc_id+'"]', $scope).prop('selected', true);
				$('input[name="transaction_amount"]', $scope).val(parseFloat(data.amount));
				$('input[name="transaction_type_id"][value="'+data.type_id+'"]', $scope).prop('checked', true);
				$('input[name="transaction_date"]', $scope).val(data.date);
				$('textarea[name="transaction_description"]', $scope).val(data.description);
				
				return this;
			},
			
			reset: function() {
				this.setMode('create');
				
				this.setData({
					acc_id: 1,
					cacc_id: 1,
					amount: 0,
					type_id: 1,
					date: moment(),
					description: ''
				});
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