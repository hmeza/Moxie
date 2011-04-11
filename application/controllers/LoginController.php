<?php

include ("application/models/loginModel.php");

class LoginController extends Zend_Controller_Action {
	private $loginModel;
	
	public function init() {
		$this->loginModel = new LoginModel();		
	}
	
	/**
	 * @desc	Perform login
	 * @param	$login		string	login
	 * @param	$password	string	password
	 */
	public function loginAction() {
		error_log('loginAction');
		$st_form = $this->getRequest()->getPost();
		$s_user = $st_form['login'];
		$s_password = $st_form['password'];
		if (!empty($s_user) && !empty($s_password)) {
			$i_result = $this->loginModel->checkLogin($s_user, $s_password);
			if ($i_result == 0) {
				error_log('login error');
				$this->view->assign('loginMessage', 'bad login');
				$this->_helper->redirector('index','expenses');
			}
			else {
				$_SESSION['user_id'] = $i_result;
				$_SESSION['user_name'] = 'Hugo';
				$this->view->assign('loginMessage', 'login OK');
				$this->_helper->redirector('index','expenses');
			}
		}
	}
	
	/**
	 * @desc	Perform logout
	 * @author	hmeza
	 * @since	2011-04-11
	 */
	public function logoutAction() {
		$_SESSION = array();
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		$b_result = session_destroy();
		if (!$b_result) error_log('MOXIE: Unable to destroy session');
		else error_log('MOXIE: Logged out');
		$this->_helper->redirector('index','expenses');
	}
}
?>