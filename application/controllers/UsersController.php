<?php

class UsersController extends Zend_Controller_Action {
	/** @var Users */
	private $usersModel;

	/** @var Categories */
	private $categories;

	/** @var Budgets */
	private $budgets;

    /** @var Tags */
    private $tags;
	
	public function init() {
		parent::init();
		$this->usersModel = new Users();
		$this->categories = new Categories();
		$this->budgets = new Budgets();
        $this->tags = new Tags();
	}
	
	private function getForm($i_userPK) {
		global $st_lang;
		$form = new Zend_Form();
		$st_user = $this->usersModel->find($i_userPK);
		$row = $st_user->current();

		$form->setName('myaccountform');
		$form->setAction('/users/update')->setMethod('post');

		$form->addElement('hidden', 'id', array('value' => $i_userPK));
		$form->addElement('text', 'login', array('label' => $st_lang['login'], 'readonly' => 'true', 'readonly' => true, 'value' => $st_user[0]['login'], 'class' => 'form-control'));
		$form->addElement('password', 'password', array('label' => $st_lang['password'], 'class' => 'form-control'));
		$form->addElement('password', 'password_check', array('label' => $st_lang['repeat_password'], 'class' => 'form-control'));
		$form->addElement('text', 'email', array('label' => $st_lang['email'], 'value' => $st_user[0]['email'], 'class' => 'form-control'));

		$st_langs = array('es' => 'Español', 'ca' => 'Catalan', 'en' => 'English');
		$multiOptions = new Zend_Form_Element_Select('language', $st_langs);
		$multiOptions->setLabel($st_lang['language']);
		$multiOptions->addMultiOptions($st_langs);
		$multiOptions->setValue($st_user[0]['language']);
        $multiOptions->setAttrib('class', 'form-control');
		$form->addElement($multiOptions);
		
		$form->addElement('submit', 'submit', array('label'=> $st_lang['user_send'], 'class' => 'form-control'));
		return $form;
	}
	
	/**
	 * Show settings.
	 */
	public function indexAction() {
        $expenses = new Transactions();
        $this->view->assign('favourites_list', $expenses->getFavourites($_SESSION['user_id']));

		$this->view->assign('form', $this->getForm($_SESSION['user_id']));
		// from categories
		if (empty($this->view->categories_form)) {
			$this->view->assign('categories_form', CategoriesController::getForm());
			$this->view->assign(
					'categories_list',
					$this->categories->mountCategoryTree(
							$this->categories->getCategoriesByUser(3),
							$_SESSION['user_id']
					)
			);
		}

		$st_categories = $this->categories->prepareCategoriesTree($this->categories->getCategoriesTree());
		foreach ($st_categories as $key => $value) {
			try {
				// get budget for this category
				$i_categoryPK = (isset($value['id3'])) ? $value['id3'] : $value['id2'];
				$o_budget = $this->budgets->fetchRow(
						$this->budgets->select()
								->where('category = ' . $i_categoryPK)
								->where('date_ended IS NULL')
				);
				$st_categories[$key]['budget'] = (!empty($o_budget)) ? $o_budget->amount : 0;
			}
			catch(Exception $e) {
				error_log("Catched error from budgets, category is $i_categoryPK and key is $key");
			}
		}

		$this->view->assign('tag_list', $this->tags->getTagsByUser($_SESSION['user_id']));
		$this->view->assign('categories', $st_categories);
		$this->view->assign('budgets_list', $this->budgets->getBudgetsDatesList());
		if(!isset($this->view->categories_collapse)) {
			$this->view->assign('categories_collapse', true);
		}
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
