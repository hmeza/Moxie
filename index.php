<html>
<head>
<link rel="stylesheet" type="text/css" href="/moxie.css"/>
<link rel="stylesheet" type="text/css" href="/dropdown.css"/>
<!-- Google Analytics script -->
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-10754954-2']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</head>
<body>
<?php
session_start();
include_once 'html/web.php';

include 'Zend/Controller/Front.php';
include_once 'Zend/Db.php';
include_once 'Zend/Db/Adapter/Pdo/Mysql.php';
include 'Zend/Registry.php';
include_once 'Zend/Db/Table.php';
include_once 'Zend/Config/Ini.php';

// Set translation
if (isset($_SESSION['user_lang'])) {
	include 'application/configs/langs/'.$_SESSION['user_lang'].'.php';
}
else {
	include 'application/configs/langs/es.php';
}

// Set config vars
switch($_SERVER['SERVER_NAME']) {
	case 'hugoboss666.no-ip.com':
	case 'moxie.com':
	case 'moxie.redirectme.net':
		$section = "staging";
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

$front = Zend_Controller_Front::getInstance();
 
// Set several module directories at once:
$front->setControllerDirectory(array(
    'default' => 'application/controllers',
	'categories'	=>	'application/controllers',
	'expenses'		=>	'application/controllers'
));

echo web_header(Zend_Registry::get('config')->moxie->app->name,
				Zend_Registry::get('config')->moxie->settings->url);
echo web_menu();

$front->run('application/controllers/');
?>
</body>
</html>
