<?php
	
	require_once __DIR__ . '/../vendor/autoload.php';
	require_once __DIR__ . '/../src/php/repository.php';
	
	/**
	 * TODO: fix twig template paths
	 * TODO: fix '/' route
	 * 
	 * https://github.com/chriso/klein.php
	 * http://www.phpclasses.org/package/790-PHP-Makes-using-mySQL-ridiculously-easy-great-docs-.html
	 */
	
	$routing = new \Klein\Klein();
	
	// ---- services ----
	$routing->respond(function ($request, $response, $service, $app) {
		
		// Lazy stored database
		$app->register('connection', function() {
			return new ezSQL_pdo('mysql:host=127.0.0.1;port=3306;dbname=lunch', 'root', '');
		});
		
		$app->register('repository', function() {
			return new Repository();
		});
		
		// Lazy stored initialized twig engine
		$app->register('twig', function() {
			
			$paths = getFolders(realpath(__DIR__.'/../src/twig'));
			$loader = new Twig_Loader_Filesystem($paths);
			
			$twig = new Twig_Environment($loader, array(
				'cache' => ((false) ? realpath(__DIR__.'/../web/cache/') : false)
			));
			
			return addTwigExtensions($twig);
		});
	});
	
	$routing->onHttpError(function ($code, $router) {
		switch ($code) {
			case 404:
				$router->response()->body(
					'Y U so lost?!'
				);
				break;
			case 405:
				$router->response()->body(
					'You can\'t do that!'
				);
				break;
			default:
				$router->response()->body(
					'Oh no, a bad error happened that caused a '. $code
				);
		}
	});
	
	// ---- test ----
	$routing->respond('GET', '/?', function ($request, $response, $service, $app) {
		return $app->twig->render('base.html.twig');
	});
	
	// ---- dashboard ----
	$routing->respond('GET', '/dashboard', function ($request, $response, $service, $app) {
		$data = array(
			'lunchAccount'		=> $app->connection->get_row("SELECT account.* FROM accounts account WHERE account.first_name = 'Lunch pot'"),
			'userData'			=> $app->connection->get_results("SELECT * FROM view_total_for_users"),
			'transactions'		=> $app->connection->get_results("SELECT * FROM view_all_transactions"),
			'accounts'			=> $app->connection->get_results("SELECT * FROM accounts"),
			'transactionTypes'	=> $app->connection->get_results("SELECT * FROM transaction_type")
		);
		
		return $app->twig->render('dashboard.html.twig', $data);
	});
	
	// ---- transaction ----
	$routing->respond('GET', '/transaction/create', function ($request, $response, $service, $app) {
		$data = array(
			'accounts'			=> $app->connection->get_results("SELECT * FROM accounts"),
			'transactionTypes'	=> $app->connection->get_results("SELECT * FROM transaction_type")
		);
		
		return $app->twig->render('transaction.html.twig', $data);
	});
	
	// ---- account ----
	$routing->respond('GET', '/account/[i:id]', function ($request, $response, $service, $app) {
		
		$data = array(
			'account' => $app->connection->get_row($app->repository->getAccountQuery($request->id))
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
	
	function addTwigExtensions($twig) {
		
		// create our own twig extension to get the full path for an asset
		$twig->addFunction(new Twig_Function('asset', function($asset) {
			return getFullHost().'/'.ltrim($asset, '/');
		}));
		
		$twig->addFunction(new Twig_Function('match', function($pattern, $subject) {
			preg_match($pattern, $subject, $matches);
			return (count($matches) > 0) ? $matches[0] : '';
		}));
		
		$twig->addFunction(new Twig_Function('matches', function($pattern, $subject) {
			return (preg_match($pattern, $subject) == 1);
		}));
		
		$twig->addFunction(new Twig_Function('replace', function($pattern, $replacement, $subject) {
			return preg_replace($pattern, $replacement, $subject);
		}));
		
		// delimit an iban string on each 4th character with a space
		$twig->addFilter(new Twig_Filter('iban', function($value) {
			
			$chars = 4;
			$parts = array();
			
			for ($i = 0; $i < ceil(strlen($value) / $chars); $i++) {
				$parts[] = substr($value, $i * $chars, $chars);
			}
			
			return join(' ', $parts);
		}));
		
		return $twig;
	}
?>