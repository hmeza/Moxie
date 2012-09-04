<html>
<head>
<meta name="google-site-verification" content="iE-pGhNJXHaAv1EEwkd5eCMStzQBHUtGZaiwKN-WJoA" />
<link rel="stylesheet" type="text/css" href="moxie.css"/>
<link rel="stylesheet" type="text/css" href="dropdown.css"/>
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
