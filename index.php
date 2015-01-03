<?php
session_start();
date_default_timezone_set("Europe/Madrid");
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));
define('APPLICATION_ENV', 'development');
set_include_path(get_include_path().PATH_SEPARATOR."./lib".PATH_SEPARATOR."../Zend");
include_once '../Zend/Zend/Application.php';

function __autoloader($s_originalClass) {
	@include_once $s_originalClass;

	$s_pathClass = str_replace("\\", "/", $s_originalClass);
	$st_path = explode("/", $s_pathClass);
	$s_class = end($st_path);

	@include_once $s_class.'.php';

	@include_once '../lib/'.$s_class.'.php';
	@include_once 'lib/'.$s_class.'.php';
	@include_once 'lib/'.$s_pathClass.'.php';

	@include_once 'strategies/'.$s_class.'.php';
	@include_once APPLICATION_PATH.'/models/'.$s_class.'.php';
	@include_once APPLICATION_PATH.'/controllers/'.$s_class.'.php';
	@include_once APPLICATION_PATH.'/views/'.$s_class.'.php';
}
spl_autoload_register("__autoloader");

try {
	$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
	$application->bootstrap()->run();
}
catch (Exception $e) {
	throw new Exception('Error bootstrapping: '.$e->getMessage());
}

?>
