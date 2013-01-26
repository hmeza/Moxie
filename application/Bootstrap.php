<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	
	protected function _initLibraries() {
		//Zend Loader
		require_once "Zend/Loader.php";  
  
		//Required classes 
		Zend_Loader::loadClass('Zend_Controller_Front');
		Zend_Loader::loadClass('Zend_Registry');  
		//Zend_Loader::loadClass('Zend_Session');
		//Zend_Loader::loadClass('Zend_Auth');  
		Zend_Loader::loadClass('Zend_Db');  
		Zend_Loader::loadClass('Zend_Db_Table');
		Zend_Loader::loadClass('Zend_Db_Adapter_Pdo_Mysql');  
		Zend_Loader::loadClass('Zend_Config_Ini');
		
		//include_once('Zend/Form.php');
		//include_once('application/models/Categories.php');
		//Zend_Loader::loadClass('Zend_Form');
	}
	
	/**
	 * Get browser platform and load library
	 */
	protected function _initBrowser() {
		include_once 'html/web.php';
		$s_viewPrefix = "";
	}
	
	/**
	 * 
	 * Init translation array
	 */
	protected function _initTranslation() {
		// Set translation
		if (isset($_SESSION['user_lang'])) {
			include 'application/configs/langs/'.$_SESSION['user_lang'].'.php';
		}
		else {
			include 'application/configs/langs/es.php';
		}
		$GLOBALS['st_lang'] = $st_lang;
	}
	
	/**
	 * Init database
	 * @throws Exception
	 */
	protected function _initDb() {
		// Set config vars
		switch($_SERVER['SERVER_NAME']) {
			case 'moxie.redirectme.net':
				$section = "staging";
				break;
			case 'moxie.dootic.com':
				$section = "production";
				break;
			case 'moxie.dev':
			default:
				$section = "development";
				break;
		}
		
		$o_config = new Zend_Config_Ini('application/configs/application.ini', $section, array('allowModifications'=>true));
		$o_registry = Zend_Registry::getInstance();
		$o_registry->set('config', $o_config);
		
		$db = new Zend_Db_Adapter_Pdo_Mysql(array(
		    'host'     => Zend_Registry::get('config')->moxie->db->host,
		    'username' => Zend_Registry::get('config')->moxie->db->username,
		    'password' => Zend_Registry::get('config')->moxie->db->password,
		    'dbname'   => Zend_Registry::get('config')->moxie->db->database
		));
		$GLOBALS['db'] = $db;
		
		if ($db != null) {
			Zend_Registry::set('db', $db);
		}
		else {
			throw new Exception('Moxie: cannot create database adapter');
		}
		Zend_Db_Table_Abstract::setDefaultAdapter($db);
	}

	/**
	 * Start Moxie
	 */
	protected function _initApp() {
	}
}
