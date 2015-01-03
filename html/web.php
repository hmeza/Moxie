<?php
// TODO: Find a proper name for this

function web_login() {
	global $st_lang;
	
	return '
	<form name="login" id="login" action="'.Zend_Registry::get('config')->moxie->settings->url.'/login/login" method="POST">
	<fieldset class="login contact">
	<legend>Login</legend>
	<table width=200>
	<tr><td><label for="username">Login</label></td><td><input type="text" name="login" id="login" maxlenght=15></td></tr>
	<tr><td><label for="username">Password</label></td><td><input type="password" name="password" id="password" maxlenght=15></td></tr>
	<tr><td colspan=2 align="right"><input type="submit" value="Login"></td></tr>
	<tr><td colspan=2 align="right"><a href="/login/newuser">'.$st_lang['new_user'].'</a>
	&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="/login/forgotpassword">'.$st_lang['forgot_password'].'</a></td></tr>
	</table>
	</fieldset>
	</form>
	';
}

function web_userData($i_userId, $s_userName) {
	global $st_lang;
	
	$s_html = $s_userName.'<br>';
	
	return $s_html;
}

function web_header($s_name, $b_loggedIn = false) {
	global $st_lang;
	
	$header = '
	<table width=100%>
	<tr>
	<td valign="top" class="moxietitle" width="233" height="65" onclick="window.location=\''.Zend_Registry::get('config')->moxie->settings->url.'\'"></td>
	<td align="right">';
	
	if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0) {
		
		$header .= web_login();
	}
	else {
		$header .= '';
	}
	
	$header .= '
	</td>
	</tr>
	</table>
	';
	
	return $header;
}

function web_menu() {
	global $st_lang;
	
	$url = $_SERVER['REQUEST_URI'];
	$st_url = explode("/", $url);
	$st_urls = array(
		'incomes' => '/incomes/index',
		'expenses' => '/expenses/index',
		'stats' => '/stats/index',
		'users' => '/users/index'
	);
	$st_lang['users'] = $st_lang['users_my_account'];
	$style = 'style="display: inline-block; padding: 5px; border-radius: 5px; background-color: #D0E8F4; color: white; border 5px solid white;"';
	
	$s_webMenu = '
	<table cellspacing=10>
	';
	if (isset($_SESSION['user_id'])) {
		$s_webMenu .= '
		<td>'.web_userData($_SESSION['user_id'], $_SESSION['user_name']).'</td>';
		
		foreach($st_urls as $key => $value) {
			$s_webMenu .= '<td'.(($st_url[1] == $key) ? ' '.$style : '').'><a href="'.Zend_Registry::get('config')->moxie->settings->url.$st_urls[$key].'">'.$st_lang[$key].'</a></td>';
		}
		$s_webMenu .= '<td><a href="'.Zend_Registry::get('config')->moxie->settings->url.'/login/logout">'.$st_lang['logout'].'</a></td>
		';
	}
	else {
		$s_webMenu .= '
		<td><a href="'.Zend_Registry::get('config')->moxie->settings->url.'/">'.$st_lang['index'].'</a></td>
		<td><a href="'.Zend_Registry::get('config')->moxie->settings->url.'/texts/about">'.$st_lang['about'].'</a></td>
		';
	}
	$s_webMenu .= '
	</table>
	';
	return $s_webMenu;
}

function web_footer() {
	return '
			</div>
			<br><br><br>
			<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/es_LA/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, \'script\', \'facebook-jssdk\'));</script>
<div id="fb-root"></div>
			<div id="bottom">
			<center>
			2013 - <a href="mailto:'.Zend_Registry::get('config')->moxie->email.'">Moxie</a> -
<div class="fb-like" data-href="'.Zend_Registry::get('config')->moxie->settings->url.'" data-send="false" data-layout="button_count" data-width="450" data-show-faces="true" data-font="arial"></div>
<div class="g-plusone" data-size="medium" data-href="'.Zend_Registry::get('config')->moxie->settings->url.'"></div>
<script type="text/javascript">
  window.___gcfg = {lang: \'es\'};

  (function() {
    var po = document.createElement(\'script\'); po.type = \'text/javascript\'; po.async = true;
    po.src = \'https://apis.google.com/js/plusone.js\';
    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>
</center></div>
			</center></div>
		</body>
</html>';
}
