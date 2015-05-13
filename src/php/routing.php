<?php
	require_once __DIR__ . '/vendor/autoload.php';
	
	// https://github.com/chriso/klein.php
	$klein = new \Klein\Klein();
	
	$klein->respond('GET', '/hello-world', function () {
	    return 'Hello World!';
	});

	$klein->dispatch();
?>