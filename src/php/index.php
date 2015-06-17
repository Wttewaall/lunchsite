<?php
	
	#!/bin/env php
	
	/**
	 * -- TODO --
	 *	. Autoload classes instead of declaring them manually
	 *		@see http://www.sitepoint.com/autoloading-and-the-psr-0-standard/
	 *		@see http://php-autoloader.malkusch.de/en/
	 *	. Clean up Routing: routes with multiple CRUD actions should go to their own Controller
	 *		@see https://github.com/chriso/klein.php/issues/134
	 *	. POST a Transaction form
	 *	. POST an Account form
	 **/
	
	$root_path	= realpath(__DIR__.'/../');
	$php_path	= realpath($root_path.'/src/php/');
	$twig_path	= realpath($root_path.'/src/twig/');
	$cache_path	= realpath($root_path.'/web/cache/');
	
	// load vendors
	require_once $root_path.'/vendor/autoload.php';
	
	// include our classes
	require_once $php_path.'/Routing.php';
	require_once $php_path.'/TwigExtensions.php';
	require_once $php_path.'/controller/TransactionController.php';
	require_once $php_path.'/enum/AccountType.php';
	require_once $php_path.'/enum/TransactionStatus.php';
	require_once $php_path.'/enum/TransactionType.php';
	require_once $php_path.'/repository/Repository.php';
	require_once $php_path.'/repository/AccountRepository.php';
	require_once $php_path.'/repository/DashboardRepository.php';
	require_once $php_path.'/repository/TransactionRepository.php';
	
	$parameters = array(
		'db_host'			=> '127.0.0.1',
		'db_port'			=> '3306',
		'db_name'			=> 'lunch',
		'db_user'			=> 'root',
		'db_pass'			=> '',
		'twig_use_cache'	=> false,
		
		'paths' => array(
			'root'	=> $root_path,
			'php'	=> $php_path,
			'twig'	=> $twig_path,
			'cache'	=> $cache_path
		)
	);
	
	// instantiate Routing with our parameters and dispatch
	$routing = new Lunchpot\Routing($parameters);
	$routing->dispatch();
	
?>