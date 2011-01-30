<?php

include ("../models/loginModel.php");

class LoginControler {
	
	
	function __construct() {
		$loginModel = new LoginModel();	
	}
	
	/**
	 * @desc	Perform login
	 * @param	$login		string	login
	 * @param	$password	string	password
	 */
	public function doLogin($login, $password) {
		global $db;
		$sql = 'SELECT * FROM users where login = '.$login.' and password = md5("'.$password.'")';
		 
		$result = $db->fetchAll($sql);
		if ($result == null) {
			
		}
	}
	
	
}


?>