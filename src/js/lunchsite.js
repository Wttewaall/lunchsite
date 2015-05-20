
function setupControls() {
	// initialize all tooltip elements
	$('[data-toggle="tooltip"]').tooltip();
	
	// initialize all select2 elements
	//$('.select2').select2();
	
	// initialize all date elements
	$('.date').datetimepicker({
		locale: 'nl',
		defaultDate: moment()
		
	}).on('focusin', function() {
		$(this).data('DateTimePicker').show();
	});
	
	// ---- form handling ----
	
	$('body').on('click', 'form .btn-cancel', function(event) {
		$modal = $(event.currentTarget).parents('.modal');
		if (!!$modal) $modal.modal('hide');
	});
	
	$('body').on('success.form.fv', '#transactionForm', transactionFormSubmitHandler);
}

function transactionFormSubmitHandler(event) {
	event.preventDefault();
	var $form = $(event.currentTarget);
	
	var data = $form.serialize();
	
	postForm('transaction/create', $form, data).done(function (data) {
		
		$modal = $(event.currentTarget).parents('.modal');
		if (!!$modal) $modal.modal('hide');
		
		bootbox.alert('Het formulier is succesvol verzonden');
		resetForm($form);
	});
}

function postForm(method, form, data) {
	console.log('ok');return;
	
	var params = '';
	$.each(data, function(property, value) {
		params += (params.length ? '&' : '') + encodeURIComponent(property) + '=' + encodeURIComponent(value);
	});
	
	// combine host, method and parameters to a url
	var url = '/' + method + '?' + params;
	
	var $controls = $('input, select, textarea, button', $(form));
	console.log('posting form...', url);return;
	return $.ajax({
		url: url,
		type: 'POST',
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

$(document).ready(function() {
	setupControls();
});