<?php
	
	$root = realpath(__DIR__.'/../');
	
	require_once $root.'/vendor/autoload.php';
	require_once $root.'/src/php/Repository.php';
	require_once $root.'/src/php/TwigExtensions.php';
	
	use \Klein\Klein;
	
	$parameters = array(
		'root'				=> $root,
		'db_host'			=> '127.0.0.1',
		'db_port'			=> '3306',
		'db_name'			=> 'lunch',
		'username'			=> 'root',
		'password'			=> '',
		'twig_use_cache'	=> true
	);
	
	/**
	 * TODO: fix twig template paths
	 * TODO: fix '/' route and .htaccess
	 *  
	 * https://github.com/chriso/klein.php
	 * http://www.phpclasses.org/package/790-PHP-Makes-using-mySQL-ridiculously-easy-great-docs-.html
	 */
	
	$routing = new Klein();
	
	// ---- services ----
	$routing->respond(function ($request, $response, $service, $app) use ($parameters) {
		
		// Lazy stored database
		$app->register('connection', function() use ($parameters) {
			
			$db_address = join(';', array(
				'host='.$parameters['db_host'],
				'port='.$parameters['db_port'],
				'dbname='.$parameters['db_name']
			));
			
			$db = new ezSQL_pdo('mysql:'.$db_address, $parameters['username'], $parameters['password']);
			
			/*
			// Cache expiry
			$db->cache_timeout = 1; // in hours

			// Specify a cache dir. Path is taken from calling script
			$db->cache_dir = 'ezsql_cache';

			// (1. You must create this dir. first!)
			// (2. Might need to do chmod 775)

			// Global override setting to turn disc caching off
			// (but not on)
			$db->use_disk_cache = true;

			// By wrapping up queries you can ensure that the default
			// is NOT to cache unless specified
			$db->cache_queries = true;
			*/
			
			return $db;
		});
		
		$app->register('repository', function() use (&$app) {
			return new Repository($app->connection);
		});
		
		// Lazy stored initialized twig engine
		$app->register('twig', function() use ($parameters) {
			
			$useCache = $parameters['twig_use_cache'];
			$paths = getFolders(realpath($parameters['root'].'/src/twig'));
			
			$loader = new Twig_Loader_Filesystem($paths);
			
			$twig = new Twig_Environment($loader, array(
				'cache' => ($useCache ? realpath($parameters['root'].'/web/cache/') : false)
			));
			
			TwigExtensions::addExtensions($twig);
			
			return $twig;
		});
	});
	
	$routing->onHttpError(function ($code, $router) {
		$app = $router->app();
		
		switch ($code) {
			case 403: {
				$response = $app->twig->render('403-forbidden.html.twig');
				$router->response()->body($response);
				break;
			}
			case 404: {
				$response = $app->twig->render('404-not-found.html.twig');
				$router->response()->body($response);
				break;
			}
			case 405: {
				$router->response()->json(array('code' => $code, 'status' => 'Method Not Allowed'));
				break;
			}
			default: {
				$router->response()->body('Oh no, a bad error happened that caused a '. $code);
			}
		}
	});
	
	// ---- root ----
	$routing->respond('GET', '/', function ($request, $response, $service, $app) {
		
		$data = array(
		    'lunchAccount'		=> $app->repository->getLunchpotAccount(),
			'userData'			=> $app->repository->getUserTotals(),
			'transactions'		=> $app->repository->getTransactions(),
			'accounts'			=> $app->repository->getMutableAccounts(),
			'transactionTypes'	=> $app->repository->getTransactionTypes(),
			'totalCash'			=> $app->repository->getTotalCash(),
			'totalBank'			=> $app->repository->getTotalBank()
		);
		
		return $app->twig->render('dashboard.html.twig', $data);
	});
	
	// ---- transaction ----
	$routing->respond('GET', '/transaction/[create:action]', function ($request, $response, $service, $app) {
		$data = array(
			'accounts'			=> $app->repository->getAccounts(),
			'transactionTypes'	=> $app->repository->getTransactionTypes()
		);
		
		return $app->twig->render('transaction.html.twig', $data);
	});
	
	$routing->respond('POST', '/transaction/add', function ($request, $response, $service, $app) {
		return var_dump($request->postParams());
	});
	
	// ---- account ----
	$routing->respond('GET', '/account/[i:id]', function ($request, $response, $service, $app) {
		
		$data = array(
			'account' => $app->repository->getAccountById($request->id)
		);
		return $app->twig->render('account.html.twig', $data);
	});
	
	$routing->dispatch();
	
	// ---- methods ----
	
	function print_json($object) {
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		print(json_encode($object));
	}
	
	function getFolders($root) {
		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST,
			RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
		);

		$paths = array($root);
		foreach ($iter as $path => $dir) {
			if ($dir->isDir()) $paths[] = $path;
		}
		
		return $paths;
	}
	
	function getFullHost($use_forwarded_host = false) {
		$s = &$_SERVER;
		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
		$sp = strtolower($s['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':'.$port;
		$host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
		$host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}
	
	function getFullURL($use_forwarded_host = false) {
		return getFullHost($use_forwarded_host) . $_SERVER['REQUEST_URI'];
	}
	
?>