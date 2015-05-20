<?php

class TwigExtensions {
	
	public static function addExtensions(Twig_Environment $twig) {
		
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
	
}