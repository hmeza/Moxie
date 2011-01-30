<?php
// TODO: Find a proper name for this

function web_header() {
	return '
	<h1>Welcome to <a href="http://moxie.dev/">Moxie</a></h1>
	';
	
}

function web_menu() {
	return '
	<table cellspacing=10>
		<td><a href="/">Index</a></td>
		<td><a href="/categories/index">Categories</a></td>
		<td><a href="/expenses/index">Expenses</a></td>
	</table>
	';
}

function web_footer() {
	
	
}

?>