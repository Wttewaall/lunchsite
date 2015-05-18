/**
 * Class ValidatorHelper
 * 
 * These methods assume that there is a Media container, or widget, with a certain html structure.
 * The event handlers are listening to the entire scope, so later added Media widgets will work immediately.
 **/

(function() {
	"use strict";
	
	// ---- public static methods ----
	
	window.ValidatorHelper = {
		init: function(scope, fields) {
			var $scope = (!!scope) ? $(scope) : $("form");
			
			var settings = $.extend({}, $.fn.formValidation.DEFAULT_OPTIONS, {
				feedbackIcons: {
					required	: 'fa fa-asterisk',
					valid		: 'glyphicon glyphicon-ok',
					invalid		: 'glyphicon glyphicon-remove',
					validating	: 'glyphicon glyphicon-refresh'
				},
				fields: {
				}
			});
			
			// inject fields into the settings
			$.extend(settings.fields, fields);
			
			$scope.on('init.field.fv', function(e, data) {
				var $parent		= data.element.parents('.form-group');
				var $icon		= $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]');
				var options		= data.fv.getOptions();
				var validators	= data.fv.getOptions(data.field).validators;
				
				if (validators.notEmpty && options.icon && options.icon.required) {
					$icon.addClass(options.icon.required).show();
				}
			});
			
			$scope.formValidation(settings);
			
			$scope.on('status.field.fv', function(e, data) {
				var $parent		= data.element.parents('.form-group');
				var $icon		= $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]');
				var options		= data.fv.getOptions();
				var validators	= data.fv.getOptions(data.field).validators;
				
				if (validators.notEmpty && options.icon && options.icon.required) {
					$icon.removeClass(options.icon.required).addClass('fa');
				}
			});
		},
		
		reset: function(scope) {
			var $scope = (!!scope) ? $(scope) : $("form");
			$scope.data('formValidation').resetForm(true);
		}
	};
	
}());