<html>
<body>
<?php
session_start();
$_SESSION['user_id'] = 1;
include_once 'html/web.php';


echo web_header();
echo web_menu();

include("../Zend/Zend/Controller/Front.php");
include_once('../Zend/Zend/Db.php');
include_once('../Zend/Zend/Db/Adapter/Pdo/Mysql.php');
include '../Zend/Zend/Registry.php';
include_once '../Zend/Zend/Db/Table.php';

$db = new Zend_Db_Adapter_Pdo_Mysql(array(
    'host'     => '127.0.0.1',
    'username' => 'root',
    'password' => '0nr3fn1',
    'dbname'   => 'moxie'
));
$GLOBALS['db'] = $db;

if ($db != null) {
	Zend_Registry::set('db', $db);
}
else {
	throw new Exception('cannot create database adapter');
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
