<?php

namespace Lunchpot;

use \ezSQL_pdo;
use \GUMP;
use \Klein\Klein;
use \Twig_Loader_Filesystem;
use \Twig_Environment;

/**
 * Class Routing
 * @package Lunchpot
 * @author Bart Wttewaall <b.wttewaall@recognize.nl>
 */
class Routing {
	
	/**
	 * @var \Klein\Klein $routing
	 */
	protected $routing;
	
	/**
	 * @var array $parameters
	 */
	protected $parameters;
	
	public function __construct(array $parameters) {
		$this->routing = new Klein();
		$this->parameters = $parameters;
	}
	
	public function dispatch() {
		
		$params = $this->parameters;
		
		// ---- services ----
		$this->routing->respond(function ($request, $response, $service, $app) use ($params) {
			
			// set this site's title
			$app->title = 'Lunchsite';
			
			// add a custom validator (all amounts need to be positive floats)
			$service->addValidator('positiveFloat', function ($str) {
				return preg_match('/^([0-9]+)([\.0-9]+)?$/', $str);
			});
			
			// -- Lazy stored database
			
			$app->register('connection', function() use ($params) {
				
				$db_address = join(';', array(
					  'host='.$params['db_host'],
					  'port='.$params['db_port'],
					'dbname='.$params['db_name']
				));
				
				$db = new ezSQL_pdo('mysql:'.$db_address, $params['db_user'], $params['db_pass']);
				
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
			
			// -- Lazy load the repositories (not all are needed for every single request)
			
			$app->register('transactionController', function() {
				return new TransactionController();
			});
			
			// -- Lazy load the repositories (not all are needed for every single request)
			
			$app->register('accountRepository', function() use ($app) {
				return new AccountRepository($app->connection);
			});
			
			$app->register('dashboardRepository', function() use ($app) {
				return new DashboardRepository($app->connection);
			});
			
			$app->register('transactionRepository', function() use ($app) {
				return new TransactionRepository($app->connection);
			});
			
			// -- Lazy stored initialized twig engine
			
			$app->register('twig', function() use ($params) {
				
				$useCache = $params['twig_use_cache'];
				$paths = $this->getFolders($params['paths']['twig']);
				
				$loader = new Twig_Loader_Filesystem($paths);
				
				$twig = new Twig_Environment($loader, array(
					'cache' => ($useCache ? $params['paths']['cache'] : false)
				));
				
				TwigExtensions::addExtensions($twig);
				
				return $twig;
			});
		});
		
		// ---- root ----
		
		$this->routing->respond('GET', '/?', function ($request, $response, $service, $app) {
			
			$data = array(
				'app'				=> $app,
			    'userData'			=> $app->dashboardRepository->getUserTotals(),
				'totalCash'			=> $app->dashboardRepository->getTotalCash(),
				'totalBank'			=> $app->dashboardRepository->getTotalBank(),
			    'lunchAccount'		=> $app->accountRepository->getLunchpotAccount(),
			    // TODO: 'userAccount' => $app->accountRepository->getAccountById($currentUser->account_id),
				'accounts'			=> $app->accountRepository->getAccounts(),
				'transactions'		=> $app->transactionRepository->findAll(true),
				'transactionTypes'	=> $app->transactionRepository->getTransactionTypes(),
			);
			
			return $app->twig->render('dashboard.html.twig', $data);
		});
		
		$this->routing->respond('GET', '/ng/?', function ($request, $response, $service, $app) {
			$response->file('/ng/index.html');
		});
		
		/*$this->routing->respond('GET', '/ng/.+', function ($request, $response, $service, $app) {
			return $response->file(__DIR__ . $request->pathname());
		});*/
		
		// ---- transaction ----
		
		/*foreach(array('TransactionController') as $controller) {
			$controller = preg_replace('/^(.+)Controller$/', '$1', $controller);
			// Include all routes defined in a file under a given namespace
			$klein->with("/$controller", "controller/$controller.php");
		}*/
		
		$this->routing->respond('POST', '/transaction/[create|read|update|delete:action]', function ($request, $response, $service, $app) {
			return $app->transactionController->{$request->action.'Action'}($request, $response, $service, $app);
		});
		
		// ---- account ----
		
		$this->routing->respond('GET', '/account/[i:id]', function ($request, $response, $service, $app) {
			
			$data = array(
				'account' => $app->accountRepository->getAccountById($request->id)
			);
			return $app->twig->render('account.html.twig', $data);
		});
		
		// -- Handle errors
		
		$this->routing->onHttpError(function ($code, $router) {
			$app = $router->app();
			
			switch ($code) {
				case 400: {
					$response = $app->twig->render('400-bad-request.html.twig');
					$router->response()->body($response);
					break;
				}
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
					$router->response()->json(array(
						'code' => $code,
						'status' => 'Method Not Allowed'
					));
					break;
				}
				default: {
					$router->response()->body('Oh no, a bad error happened that caused a '. $code);
				}
			}
		});
		
		$this->routing->dispatch();
		
	}
	
	// ---- methods ----
	
	protected function print_json($object) {
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		print(json_encode($object));
	}
	
	protected function getFolders($root) {
		$iter = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST,
			\RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
		);

		$paths = array($root);
		foreach ($iter as $path => $dir) {
			if ($dir->isDir()) $paths[] = $path;
		}
		
		return $paths;
	}
	
}