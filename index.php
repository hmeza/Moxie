<html>
<head>
<link rel="stylesheet" type="text/css" href="moxie.css"/>
<link rel="stylesheet" type="text/css" href="dropdown.css"/>
</head>
<body>
<div style="min-height: 100%; height: auto !important; height: 100%; margin: 0 auto -4em;">
<?php
session_start();

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));
define('APPLICATION_ENV', 'development');
include_once 'Zend/Application.php';
try {
	$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
	$application->bootstrap()->run();
}
catch (Exception $e) {
	throw new Exception('Error bootstrapping: '.$e->getMessage());
}
?>
</div>
<br><br><br>
<?php
//echo web_footer();
?>
</body>
</html>
