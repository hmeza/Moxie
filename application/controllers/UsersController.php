<?php

class UsersController extends BaseController {
	private $usersModel;
	
	public function init() {
		$this->usersModel = new loginModel();
	}
	
	private function getForm($i_userPK) {
		include_once('Zend/Form.php');
		include_once('Zend/Form/Element/Select.php');
		$form = new Zend_Form();
		$st_user = $this->usersModel->find($i_userPK);
		$row = $st_user->current();
		
		$form->setAction('/users/update')->setMethod('post');
		
		$form->addElement('hidden', 'id', array('value' => $i_userPK));
		$form->addElement('text', 'login',
							array('label' => 'Login', 'readonly' => 'true', 'readonly' => true, 'value' => $st_user[0]['login']));
		$form->addElement('password', 'password', array('label' => 'Password'));
		$form->addElement('password', 'password_check', array('label' => 'Repeat password'));
		$form->addElement('text', 'email', array('label' => 'Email', 'value' => $st_user[0]['email']));

		$st_langs = array('es' => 'Español', 'en' => 'English');
		$multiOptions = new Zend_Form_Element_Select('language', $st_langs);
		$multiOptions->setLabel('Language');
		$multiOptions->addMultiOptions($st_langs);
		$multiOptions->setValue($st_user[0]['language']);
		$form->addElement($multiOptions);
		
		$form->addElement('submit', 'submit', array('label'=>'Send'));
		return $form;
	}
	
	/**
	 * @desc	Show My account.
	 * @param	$login		string	login
	 * @param	$password	string	password
	 */
	public function indexAction() {
		$this->view->assign('form', $this->getForm($_SESSION['user_id']));
	}
	
	/**
	 * Update user parameters.
	 */
	public function updateAction() {
		$st_params = $this->getRequest()->getPost();
		$i_userPK = $st_params['id'];
		
		unset($st_params['id']);
		unset($st_params['login']);
		unset($st_params['password_check']);
		unset($st_params['submit']);
		if (!empty($st_params['password'])) {
			error_log('changing password');
			$st_updatePassword = array('password' => md5($st_params['password']));
			try {
				$this->usersModel->update($st_updatePassword, 'id = '.$i_userPK);
			}
			catch (Exception $e) {
				error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
			}
		}
		unset($st_params['password']);
		if (!empty($st_params)) {
			try {
				error_log('changing '.print_r($st_params,true));
				$this->usersModel->update($st_params, 'id = '.$i_userPK);
				$_SESSION['user_lang'] = $st_params['language'];
				include 'application/configs/langs/'.$_SESSION['user_lang'].'.php';
			}
			catch(Exception $e) {
				error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
			}
		}
		$this->_helper->redirector('index','users');
	}
}
?>
