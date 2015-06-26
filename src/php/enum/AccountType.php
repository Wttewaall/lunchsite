<?php

namespace Lunchpot;

class AccountType {
	
	const EMPLOYEE		= 'EMPLOYEE';
	const INTERN		= 'INTERN';
	const POT			= 'POT';
	const SUPERMARKET	= 'SUPERMARKET';
	const BANK			= 'BANK';
	
	public static $USER_TYPES = array(
		self::EMPLOYEE,
		self::INTERN
	);
	
	public static $ALL_TYPES = array(
		self::EMPLOYEE,
		self::INTERN,
		self::POT,
		self::SUPERMARKET,
		self::BANK
	);
	
}