<?php
// TODO: Find a proper name for this

function web_login() {
	return '
	<form name="login" id="login" action="/login/login" method="POST">
	<fieldset class="login contact">
	<legend>Login</legend>
	<table width=200>
	<tr><td><label for="username">Login</label></td><td><input type="text" name="login" id="login" maxlenght=15></td></tr>
	<tr><td><label for="username">Password</label></td><td><input type="password" name="password" id="password" maxlenght=15></td></tr>
	<tr><td colspan=2 align="left" id="submit-go"><input type="submit"></td></tr>
	<tr><td colspan=2 align="right"><a href="/login/newuser">Already don\'t have an account?</a></td></tr>
	</table>
	</fieldset>
	</form>
	';
}

function web_userData($i_userId, $s_userName) {
	echo $s_userName.' <a href="/login/logout">Logout</a>';
}

function web_header($s_name, $b_loggedIn = false) {
	$header = '
	<table width=100%>
	<tr>
	<td valign="top"><h1>Welcome to <a href="http://moxie.dev/">'.$s_name.'</a></h1></td>
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
	$s_webMenu = '
	<table cellspacing=10>
	';
	if (isset($_SESSION['user_id'])) {
		$s_webMenu .= '
		<td><a href="/categories/index">Categories</a></td>
		<td><a href="/budgets/index">Budget</a></td>
		<td><a href="/incomes/index">Incomes</a></td>
		<td><a href="/expenses/index">Expenses</a></td>
		';
	}
	else {
		$s_webMenu .= '
		<td><a href="/">Index</a></td>
		<td><a href="/texts/about">About</a></td>
		<td><a href="/texts/benefits">Benefits</a></td>
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