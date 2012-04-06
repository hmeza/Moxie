<?php
// TODO: Find a proper name for this

function web_login() {
	global $st_lang;
	
	return '
	<form name="login" id="login" action="/login/login" method="POST">
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
	
	$s_html = $s_userName.'<br>
	
	<a href="/users/index">'.$st_lang['users_my_account'].'</a>&nbsp;&nbsp;&nbsp;
	<a href="/login/logout">'.$st_lang['logout'].'</a>';
	
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
		$header .= web_userData($_SESSION['user_id'], $_SESSION['user_name']);
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
	
	$s_webMenu = '
	<table cellspacing=10>
	';
	if (isset($_SESSION['user_id'])) {
		$s_webMenu .= '
		<td><a href="/categories/index">'.$st_lang['categories'].'</a></td>
		<td><a href="/incomes/index">'.$st_lang['incomes'].'</a></td>
		<td><a href="/expenses/index">'.$st_lang['expenses'].'</a></td>
		<td><a href="/stats/index">'.$st_lang['stats'].'</a></td>
		';
	}
	else {
		$s_webMenu .= '
		<td><a href="/">'.$st_lang['index'].'</a></td>
		<td><a href="/texts/about">'.$st_lang['about'].'</a></td>
		';
	}
	$s_webMenu .= '
	</table>
	';
	return $s_webMenu;
}

function web_footer() {
	return '
	<table><td>&copy; 2011 Hytsolutions.com</td></table>
	';	
}

?>