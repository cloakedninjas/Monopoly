<?php

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

if (APPLICATION_ENV == 'dev') {
	$lib_path = realpath(dirname(__FILE__) . '/../../_libs/zf');
	$app_path = realpath(dirname(__FILE__) . '/../application');
	
	//$app_path = '/home/recombob/application';
}
else {
	$lib_path = realpath(dirname(__FILE__) . '/_lib/zf');
	$app_path = realpath(dirname(__FILE__) . '/_application');
}


// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $app_path);

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array($lib_path, get_include_path())));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
$application->bootstrap()->run();

