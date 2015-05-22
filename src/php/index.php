<?php
	
	/**
	 * -- TODO --
	 *	. Clean up Routing: routes with multiple CRUD actions should go to their own Controller
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
	require_once $php_path.'/Repository.php';
	require_once $php_path.'/TwigExtensions.php';
	
	$parameters = array(
		'db_host'			=> '127.0.0.1',
		'db_port'			=> '3306',
		'db_name'			=> 'lunch',
		'username'			=> 'root',
		'password'			=> '',
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