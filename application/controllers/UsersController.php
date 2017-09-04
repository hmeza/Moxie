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
		$form->addElement('text', 'login',
							array('label' => $st_lang['login'], 'readonly' => 'true', 'readonly' => true, 'value' => $st_user[0]['login']));
		$form->addElement('password', 'password', array('label' => $st_lang['password']));
		$form->addElement('password', 'password_check', array('label' => $st_lang['repeat_password']));
		$form->addElement('text', 'email', array('label' => $st_lang['email'], 'value' => $st_user[0]['email']));

		$st_langs = array('es' => 'Español', 'ca' => 'Catalan', 'en' => 'English');
		$multiOptions = new Zend_Form_Element_Select('language', $st_langs);
		$multiOptions->setLabel($st_lang['language']);
		$multiOptions->addMultiOptions($st_langs);
		$multiOptions->setValue($st_user[0]['language']);
		$form->addElement($multiOptions);
		
		$form->addElement('submit', 'submit', array('label'=> $st_lang['user_send']));
		return $form;
	}

	private function getCategoriesForm() {
		global $st_lang;
		$form  = new Zend_Form();

		$form->setAction('/categories/add')->setMethod('post');
		$form->addElement('select', 'parent', array(
						'label' => $st_lang['category_parent'],
						'multioptions' => $this->categories->getCategoriesForSelect(3),
				)
		);
		$form->addElement('text', 'name', array('label' => $st_lang['category_name']));
		$form->addElement('text', 'description', array('label' => $st_lang['category_description']));

		$categoryTypes = array(Categories::EXPENSES => $st_lang['category_expense'], Categories::INCOMES => $st_lang['category_income'], Categories::BOTH => $st_lang['category_both']);
		$types = new Zend_Form_Element_Radio('type');
		$types->setRequired(true)  // field required
		->setLabel($st_lang['category_type'])
				->setValue(Categories::BOTH) // first radio button selected
				->setMultiOptions($categoryTypes);  // add array of values / labels for radio group
		$form->addElement($types);

		$form->addElement('submit','submit', array('label' => $st_lang['category_send']));

		return $form;
	}
	
	/**
	 * Show settings.
	 * @param   string	$login
	 * @param   string	$password
	 */
	public function indexAction() {
		$st_budgetsList = array();
		$this->view->assign('form', $this->getForm($_SESSION['user_id']));
		// from categories
		if (empty($this->view->categories_form)) {
			$this->view->assign('categories_form', $this->getCategoriesForm());
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