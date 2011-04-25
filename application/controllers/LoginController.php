<?php

include ("application/models/loginModel.php");

class LoginController extends Zend_Controller_Action {
	private $loginModel;
	
	public function init() {
		$this->loginModel = new LoginModel();		
	}
	
	private function getForm() {
		include('Zend/Form.php');
		$form = new Zend_Form();
		
		$form->addElement('text', 'login', array('label' => 'Login'));
		$form->addElement('password', 'password', array('label' => 'Password'));
		$form->addElement('password', 'password_check', array('label' => 'Repeat password'));
		$form->addElement('text', 'email', array('label' => 'Email'));
		$form->addElement('submit', 'submit', array('label'=>'Send'));
		return $form;
	}
	
	/**
	 * @desc	Perform login
	 * @author	hmeza
	 * @since	2011-04-11
	 * @param	$login		string	login
	 * @param	$password	string	password
	 */
	public function loginAction() {
		$st_form = $this->getRequest()->getPost();
		$s_user = $st_form['login'];
		$s_password = $st_form['password'];
		if (!empty($s_user) && !empty($s_password)) {
			$st_result = $this->loginModel->checkLogin($s_user, $s_password);
			if ($st_result == 0) {
				error_log('login error');
				$this->view->assign('loginMessage', 'bad login');
				$this->_helper->redirector('index','expenses');
			}
			else {
				$_SESSION['user_id'] = $st_result['id'];
				$_SESSION['user_name'] = $st_result['login'];
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
	
	/**
	 * @desc	Shows the new user form
	 * @author	hmeza
	 * @since	2011-04-25
	 */
	public function newuserAction() {
		$this->view->assign('form', $this->getForm());
	}
	
	/**
	 * @desc	Registers an user and populates categories with demo data.
	 * @author	hmeza
	 * @since	2011-04-25
	 */
	public function registeruserAction() {
		include 'application/models/Categories.php';
		
		$st_form = $this->getRequest()->getPost();
		$data = array(
			'login'		=> $st_form['login'],
			'password'	=> md5($st_form['password']),
			'email'		=> $st_form['email']
		);
		try {
			$i_lastInsertId = $this->loginModel->insert($data);
		}
		catch (Exception $e) {
			// Check if user exists
			// Return to user registering
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
			error_log('MOXIE: Cannot create user');
			$this->_helper->redirector('newuser','login');
		}
		// create default categories
		$o_categories = new Categories();
		// first insert root category
		$st_categoriesData = array(
			'user_owner'	=>	$i_lastInsertId,
			'description'	=>	'root category'
		);
		try {
			$i_rootCategory = $o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$i_rootCategory,
				'name'			=>	'Hogar',
				'description'	=>	'Hogar',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$i_rootCategory,
				'name'			=>	'Comida',
				'description'	=>	'Comida',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$i_rootCategory,
				'name'			=>	'Diversión',
				'description'	=>	'Salidas, cenas fuera, ocio, etc.',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$i_rootCategory,
				'name'			=>	'Tecnología',
				'description'	=>	'Tecnología',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$i_rootCategory,
				'name'			=>	'Regalos',
				'description'	=>	'Navidad, reyes, aniversarios, san Valentín, etc.',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$i_rootCategory,
				'name'			=>	'Ropa',
				'description'	=>	'Ropa',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$i_rootCategory,
				'name'			=>	'Varios',
				'description'	=>	'Otros gastos',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);

			$o_foodCategory = $o_categories->fetchRow(
					$o_categories->select()->where('name = "Comida" AND user_owner = '.$i_lastInsertId)
				);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$o_foodCategory->id,
				'name'			=>	'Casa',
				'description'	=>	'Comida comprada para casa',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$o_foodCategory->id,
				'name'			=>	'Fuera',
				'description'	=>	'Comidas fuera de casa',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
			$st_categoriesData = array(
				'user_owner'	=>	$i_lastInsertId,
				'parent'		=>	$o_foodCategory->id,
				'name'			=>	'Café',
				'description'	=>	'Cafés, bollería durante el día, desayuno en cafetería, etc.',
				'type'			=>	3
			);
			$o_categories->insert($st_categoriesData);
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
			error_log('MOXIE: Cannot populate user with demo categories');
		}
	}
}
?>