<?php
date_default_timezone_set("Europe/Madrid");
define('APPLICATION_PATH', realpath(dirname(__FILE__)) . '/../../application/');
include_once APPLICATION_PATH . '/../vendor/zendframework/zendframework1/library/Zend/Application.php';
set_include_path(get_include_path().PATH_SEPARATOR. APPLICATION_PATH . "/../vendor/zendframework/zendframework1/library");

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

require_once "Zend/Loader.php";
//Required classes
Zend_Loader::loadClass('Zend_Controller_Front');
Zend_Loader::loadClass('Zend_Registry');
Zend_Loader::loadClass('Zend_Db');
Zend_Loader::loadClass('Zend_Db_Table');
Zend_Loader::loadClass('Zend_Db_Adapter_Pdo_Mysql');
Zend_Loader::loadClass('Zend_Config_Ini');
Zend_Loader::loadClass('Zend_Test_PHPUnit_ControllerTestCase');
