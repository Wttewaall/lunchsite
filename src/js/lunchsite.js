$(document).ready(function() {

	$('.select2').select2();
	
	 $('.date').datetimepicker({
	 	locale: 'nl',
	 	defaultDate: moment()
	 	
	 }).on('focusin', function() {
	 	$(this).data('DateTimePicker').show();
	 });

});