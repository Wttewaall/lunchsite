
$(document).ready(function() {
	
	// set locale
	moment.locale('nl');
	
	// initialize all tooltip elements
	$('[data-toggle="tooltip"]').tooltip({
		container: 'body'
	});
	
	// initialize all date elements
	$('.date').datetimepicker({
		locale: 'nl',
		defaultDate: moment()
		
	}).on('focusin', function() {
		$(this).data('DateTimePicker').show();
	});
	
	$('.moment-duration').each(function(index, value) {
		$element = $(value);
		var timeDiff = parseInt($element.html());
		var duration = moment.duration(timeDiff).humanize();
		$element.html(duration + ' geleden');
	});
	
	// ---- form handling ----
	
	$('body')
		.on('click', "#createTransactionButton", transactionModalOpenHandler)
		.on('click', 'form .btn-cancel', modalCloseHandler)
		.on('success.form.fv', '#transactionForm', transactionFormSubmitHandler)
		.on("click", ".transactions-list .list-group-item", transactionListGroupItemClickHandler)
		.on("click", ".accounts-list .list-group-item", accountsListGroupItemClickHandler);
});

function postForm(method, form, data) {
	
	var params = '';
	if (typeof(data) == 'object') {
		$.each(data, function(property, value) {
			params += '&' + encodeURIComponent(property) + '=' + encodeURIComponent(value);
		});
		
	} else if (typeof(data) == 'string') {
		params = data;
	}
	
	// combine host, method and parameters to a url
	var url = '/' + method + '?' + params;
	
	var $controls = $('input, select, textarea, button', $(form));
	var $submitButton = $('.btn-submit', $(form));
	
	return $.ajax({
		url: url,
		type: 'POST',
		beforeSend: function(jqXHR, settings) {
			toggleThrobber($submitButton, true);
			$controls.attr('disabled', 'disabled');
		},
		error: function (jqXHR, textStatus, errorThrown) {
			bootbox.alert("Er is een fout opgetreden tijdens het versturen van de data.");
			console.error(jqXHR.status + ":" + errorThrown);
		},
		complete: function(jqXHR, textStatus) {
			toggleThrobber($submitButton, false);
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

function toggleThrobber(button, show) {
	var $button = $(button);
	var $icon = $button.find('i');
	var $throbber = $button.find('.throbber');
	if (!!$icon && !!$throbber) $icon.toggle(!show);
	if (!!$throbber) $throbber.toggle(show);
}

// ---- event handlers ----

function transactionModalOpenHandler(event) {
	var $modal = $('#transactionModal');
	var $transactionModal = $modal.transactionModal();
	$transactionModal.reset();
	$modal.modal('show');
}

function modalCloseHandler(event) {
	$modal = $(event.currentTarget).parents('.modal');
	if (!!$modal) $modal.modal('hide');
}

// TODO: move to jquery.transaction.modal.js
function transactionFormSubmitHandler(event) {
	event.preventDefault();
	var $form = $(event.currentTarget);
	
	var $modal = $('#transactionModal');
	var $transactionModal = $modal.transactionModal();
	var data = $transactionModal.getData();
	
	var url = 'transaction/'+$transactionModal.getMode();
	
	postForm(url, $form, data).done(function (data) {
		
		$modal = $(event.currentTarget).parents('.modal');
		if (!!$modal) $modal.modal('hide');
		
		bootbox.alert('Het formulier is succesvol verstuurd');
		resetForm($form);
	});
}

function transactionListGroupItemClickHandler(event) {
	var id = $(event.currentTarget).data('id');
	
	var $modal = $('#transactionModal');
	var $transactionModal = $modal.transactionModal();
	$transactionModal.setMode('update');
	$transactionModal.getTransaction(id);
	$modal.modal('show');
}

function accountsListGroupItemClickHandler(event) {
	var id = $(event.currentTarget).data('id');
	
	console.log("edit account id:", id);
}