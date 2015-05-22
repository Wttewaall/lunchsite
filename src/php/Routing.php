<?php

namespace Lunchpot;

use \ezSQL_pdo;
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
			
			// Lazy stored database
			$app->register('connection', function() use ($params) {
				
				$db_address = join(';', array(
					'host='.$params['db_host'],
					'port='.$params['db_port'],
					'dbname='.$params['db_name']
				));
				
				$db = new ezSQL_pdo('mysql:'.$db_address, $params['username'], $params['password']);
				
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
			
			$app->register('repository', function() use ($app) {
				return new Repository($app->connection);
			});
			
			// Lazy stored initialized twig engine
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
		
		$this->routing->onHttpError(function ($code, $router) {
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
		$this->routing->respond('GET', '/', function ($request, $response, $service, $app) {
			
			$data = array(
			    'lunchAccount'		=> $app->repository->getLunchpotAccount(),
				'userData'			=> $app->repository->getUserTotals(),
				'transactions'		=> $app->repository->getDetailedTransactionsList(),
				'accounts'			=> $app->repository->getMutableAccounts(),
				'transactionTypes'	=> $app->repository->getTransactionTypes(),
				'totalCash'			=> $app->repository->getTotalCash(),
				'totalBank'			=> $app->repository->getTotalBank()
			);
			
			return $app->twig->render('dashboard.html.twig', $data);
		});
		
		// ---- transaction ----
		$this->routing->respond('GET', '/transaction/[create:action]', function ($request, $response, $service, $app) {
			$data = array(
				'accounts'			=> $app->repository->getAccounts(),
				'transactionTypes'	=> $app->repository->getTransactionTypes()
			);
			
			return $app->twig->render('transaction.html.twig', $data);
		});
		
		$this->routing->respond('POST', '/transaction/add', function ($request, $response, $service, $app) {
			return var_dump($request->postParams());
		});
		
		// ---- account ----
		$this->routing->respond('GET', '/account/[i:id]', function ($request, $response, $service, $app) {
			
			$data = array(
				'account' => $app->repository->getAccountById($request->id)
			);
			return $app->twig->render('account.html.twig', $data);
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