<html>
<body>
<?php
include_once 'html/web.php';

echo web_header();
echo web_menu();

include("../Zend/Zend/Controller/Front.php");
include_once('../Zend/Zend/Db.php');
include_once('../Zend/Zend/Db/Adapter/Pdo/Mysql.php');

$db = new Zend_Db_Adapter_Pdo_Mysql(array(
    'host'     => '127.0.0.1',
    'username' => 'root',
    'password' => '0nr3fn1',
    'dbname'   => 'moxie'
));
$GLOBALS['db'] = $db;

$front = Zend_Controller_Front::getInstance();
 
// Set several module directories at once:
$front->setControllerDirectory(array(
    'default' => 'application/controllers',
	'categories'	=>	'application/controllers',
	'expenses'		=>	'application/controllers'/*,
    'blog'    => '../modules/blog/controllers',
    'news'    => '../modules/news/controllers',*/
));

$front->run('application/controllers/');
?>
</body>
</html>