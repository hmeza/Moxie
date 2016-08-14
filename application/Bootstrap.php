<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	
	protected function _initLibraries() {
		//Zend Loader
		require_once "Zend/Loader.php";  
  
		//Required classes 
		Zend_Loader::loadClass('Zend_Controller_Front');
		Zend_Loader::loadClass('Zend_Registry');  
		Zend_Loader::loadClass('Zend_Db');  
		Zend_Loader::loadClass('Zend_Db_Table');
		Zend_Loader::loadClass('Zend_Db_Adapter_Pdo_Mysql');  
		Zend_Loader::loadClass('Zend_Config_Ini');
	}
	
	/**
	 * Get browser platform and load library
	 */
	protected function _initBrowser() {
		Zend_Layout::startMvc(array('layout' => 'default'));
		$s_viewPrefix = "";
//		$this->headScript()->appendFile('js/incomes/stats.js');
	}
	
	/**
	 * Init translation array
	 */
	protected function _initTranslation() {
		// Set translation
		$s_lang = isset($_SESSION['user_lang']) ? $_SESSION['user_lang'] : 'es';
		include 'application/configs/langs/'.$s_lang.'.php';
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
			case 'integration.dootic.com':
			case 'testing':
				$section = "testing";
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
		    'dbname'   => Zend_Registry::get('config')->moxie->db->database,
			'charset'  => Zend_Registry::get('config')->moxie->db->charset
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

	protected function _initLog() {
		$o_config = Zend_Registry::getInstance()->get('config');
		ini_set('error_prepend_string', $o_config->error_prepend_string);
	}
	
	/**
	 * Start Moxie
	 */
	protected function _initApp() {
	}
}
