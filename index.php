<html>
<body>
<?php
session_start();
$_SESSION['user_id'] = 1;
include_once 'html/web.php';


echo web_header();
echo web_menu();

include 'Zend/Controller/Front.php';
include_once 'Zend/Db.php';
include_once 'Zend/Db/Adapter/Pdo/Mysql.php';
include 'Zend/Registry.php';
include_once 'Zend/Db/Table.php';
include_once 'Zend/Config/Ini.php';

// Set config vars
switch($_SERVER['SERVER_NAME']) {
	case 'moxie.dev':
	case 'hugoboss666.no-ip.com':
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

$front = Zend_Controller_Front::getInstance();
 
// Set several module directories at once:
$front->setControllerDirectory(array(
    'default' => 'application/controllers',
	'categories'	=>	'application/controllers',
	'expenses'		=>	'application/controllers'
));

$front->run('application/controllers/');
?>
</body>
</html>
