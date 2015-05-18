$(document).ready(function() {
	setupControls();
});

function setupControls() {
	// initialize all tooltip elements
	$('[data-toggle="tooltip"]').tooltip();
	
	// initialize all select2 elements
	$('.select2').select2();
	
	// initialize all date elements
	$('.date').datetimepicker({
		locale: 'nl',
		defaultDate: moment()
		
	}).on('focusin', function() {
		$(this).data('DateTimePicker').show();
	});
	
	// ---- forms ----

	/*ValidatorHelper.init('#transactionForm', {
		transaction_amount_name: {
			validators: {
				numeric: {}
			}
		},
		email: {
			validators: {
				emailAddress: {}
			}
		},
		iban: {
			validators: {
				iban: {}
			}
		}
	});

	ValidatorHelper.init('#accountForm', {
		account_iban: {
			validators: {
				iban: {}
			}
		}
	});*/

	$('#transactionForm').on('success.form.bv', function(event) {
		event.preventDefault();
		var $form = $(event.currentTarget);
		
		var data = $form.serialize();
		
		postForm('transaction/add', $form, data).done(function (data) {
			bootbox.alert('Het formulier is succesvol verzonden');
			resetForm($form);
		});
	});
}

function postForm(method, form, data) {
	
	var params = '';
	$.each(data, function(property, value) {
		params += (params.length ? '&' : '') + encodeURIComponent(property) + '=' + encodeURIComponent(value);
	});
	
	// combine host, method and parameters to a url
	var url = '/' + method + '?' + params;
	
	var $controls = $('input, select, textarea, button', $(form));
	
	return $.ajax({
		url: url,
		type: 'GET',
		dataType: 'json',
		beforeSend: function(jqXHR, settings) {
			$controls.attr('disabled', 'disabled');
		},
		error: function (jqXHR, textStatus, errorThrown) {
			bootbox.alert("Er is een fout opgetreden tijdens het versturen van de data.");
			console.error(jqXHR.status + ":" + errorThrown);
		},
		complete: function(jqXHR, textStatus) {
			$controls.removeAttr('disabled');
		}
	});
}

function resetForm(form) {
	var $form = $(form);
	
	if (!!$form[0].reset) {
		$form[0].reset();
		
	} else {
		$form.data('bootstrapValidator').resetForm(true);
	}
}