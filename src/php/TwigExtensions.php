<?php

namespace Lunchpot;

use \Twig_Environment;
use \Twig_Function;
use \Twig_Filter;

/**
 * Class TwigExtensions
 * @package Lunchpot
 * @author Bart Wttewaall <b.wttewaall@recognize.nl>
 */
class TwigExtensions {
	
	public static function addExtensions(Twig_Environment $twig) {
		
		// create our own twig extension to get the full path for an asset
		$twig->addFunction(new Twig_Function('asset', function($asset) {
			return TwigExtensions::getFullHost().'/'.ltrim($asset, '/');
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
	
	public static function getFullHost($use_forwarded_host = false) {
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
	
	public static function getFullURL($use_forwarded_host = false) {
		return $this->getFullHost($use_forwarded_host) . $_SERVER['REQUEST_URI'];
	}
	
}