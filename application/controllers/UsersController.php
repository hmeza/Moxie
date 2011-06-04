<?php

include ("application/models/loginModel.php");

class UsersController extends Zend_Controller_Action {
	private $usersModel;
	
	public function init() {
		$this->usersModel = new loginModel();
	}
	
	private function getForm($i_userPK) {
		include('Zend/Form.php');
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
	public function indexAction() {
		$this->view->assign('form', $this->getForm($_SESSION['user_id']));
	}
	
	public function updateAction() {
		$st_params = $this->getRequest()->getPost();
		$i_userPK = $st_params['id'];
		
		unset($st_params['id']);
		unset($st_params['login']);
		unset($st_params['password_check']);
		unset($st_params['submit']);
		$st_params['password'] = md5($st_params['password']);
		$this->usersModel->update($st_params, 'id = '.$i_userPK);
		$this->_helper->redirector('index','users');
	}
}
?>
