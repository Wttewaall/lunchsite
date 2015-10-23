/*
	TODO:
	https://github.com/michaelbromley/angularUtils/tree/master/src/directives/pagination#working-with-asynchronous-data
*/

angular.module('LunchsiteApp', [
	//'lunchsite.transaction',
	'ngMaterial',			// Angular Material
	//'ngStorage',			// Storage for localStorage and sessions
	//'ngFileUpload',
])

.constant('$parameters', {
	version			: '1.2.1',
	html5Mode		: false,
	debugLogging	: true,
	httpCache		: false,
	
	paths			: {
		img		: '/img',
		avatars	: '/img/avatars',
		logos	: '/img/logos'
	},
	
	theme			: {
		primaryPalette	: 'brown',
		accentPalette	: 'red'
	}
})

.config(function ($locationProvider, $logProvider, $parameters) {
	
	// turn the logging on or off
	$logProvider.debugEnabled($parameters.useDebugLogging);

	// set html5 mode
	$locationProvider.html5Mode($parameters.html5Mode);
})

.config(function($mdThemingProvider, $mdIconProvider, $parameters) {
	
	/*$mdIconProvider
		.defaultIconSet("./assets/svg/avatars.svg", 128)
		.icon("menu"       , "./assets/svg/menu.svg"        , 24)
		.icon("share"      , "./assets/svg/share.svg"       , 24)
		.icon("google_plus", "./assets/svg/google_plus.svg" , 512)
		.icon("hangouts"   , "./assets/svg/hangouts.svg"    , 512)
		.icon("twitter"    , "./assets/svg/twitter.svg"     , 512)
		.icon("phone"      , "./assets/svg/phone.svg"       , 512);*/
	
	var theme = $mdThemingProvider.theme('default');
	angular.forEach($parameters.theme, function(value, key) {
		if (angular.isFunction(theme[key])) theme[key](value);
	});
	
	/*$mdThemingProvider.theme('default')
		.primaryPalette($parameters.theme.primaryPalette)
		.accentPalette($parameters.theme.accentPalette);*/
});