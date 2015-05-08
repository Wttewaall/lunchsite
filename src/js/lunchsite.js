$(document).ready(function() {
	
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
	
});