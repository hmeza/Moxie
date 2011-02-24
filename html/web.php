<?php
// TODO: Find a proper name for this

function web_header($s_name) {
	return '
	<h1>Welcome to <a href="http://moxie.dev/">'.$s_name.'</a></h1>
	';
	
}

function web_menu() {
	return '
	<table cellspacing=10>
		<td><a href="/">Index</a></td>
		<td><a href="/categories/index">Categories</a></td>
		<td><a href="/incomes/index">Incomes</a></td>
		<td><a href="/expenses/index">Expenses</a></td>
	</table>
	';
}

function web_footer() {
	return '
	<table><td>&copy; 2011 Hytsolutions.com</td></table>
	';	
}

?>