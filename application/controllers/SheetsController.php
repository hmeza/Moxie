<?php
/** Zend_Controller_Action */
class SheetsController extends Zend_Controller_Action
{
	private $sheetModel;
	
	public function init() {
		$this->sheetModel = new SharedExpensesSheet();
	}
	
	/**
	 * Show sheets page and all my sheets.
	 */
	public function indexAction() {
		$sheets = $this->sheetModel->get_by_user_match($_SESSION['user_id']);
		$this->view->assign('sheet_list', $sheets); 
	}
	
	/**
	 * List a single sheet.
	 */
	public function viewAction() {
		$categories = new Categories();
		$id = $this->getRequest()->getParam('id', null);
		$sheet = $this->sheetModel->get_by_unique_id($id);
		$this->view->assign('sheet', $sheet);
		if ($this->getRequest()->getParam('errors', null)) {
			$this->view->assign('errors', $this->getRequest()->getParam('errors'));
		}
		$this->view->assign('categories', $categories->getCategoriesForView(Categories::EXPENSES));
		//$this->view->assign('sheet_form', $this->getForm($sheet['users'], $id));
	}

	public function createAction() {
		if (isset($_POST) && !empty($_POST)) {
			try {
				if (empty($_POST['name'])) {
					throw new Exception("Please set name for sheet");
				}
				$data = array(
						'user_owner' => $_SESSION['user_id'],
						'name' => $this->getRequest()->getParam('name', ''),
						'unique_id' => uniqid()
				);
				$id = $this->sheetModel->insert($data);
				$sheet = $this->sheetModel->find($id)->current();
				// add creator as first user
				$sheetUser = new SharedExpensesSheetUsers();
				$sheetUser->insert(array(
						'id_sheet' => $id,
						'id_user' => $_SESSION['user_id']
				));
				$this->view->assign('sheet', $sheet);
			}
			catch(Exception $e) {
				$errors = array($e->getMessage());
				$this->view->assign('errors', $errors);
			}
			$this->redirect('/sheets/view/id/'.$sheet['unique_id']);
		}
		// else render GET page
	}
	
	public function addAction() {
		try  {
			$sheet = $this->getSheet();
		}
		catch(Exception $e) {
			// return 404
			$this->view->assign('errors', array('Sheet not found'));
			//$this->render('index', 'expenses');
			$this->redirect('/sheets/view/id/'.$this->getRequest()->getParam('id_sheet'));
		}
		try {
			$sharedExpenseModel = new SharedExpenses();
			$data = array(
					'id_sheet' => $sheet['id'],
					'id_sheet_user' => $this->getRequest()->getParam('id_sheet_user'),
					'amount' => $this->getRequest()->getParam('amount'),
					'note' => $this->getRequest()->getParam('note', ''),
					'date' => $this->getRequest()->getParam('date'),
			);
			$sharedExpenseModel->insert($data);
		}
		catch(Exception $e) {
			// return 500 / error message
			error_log($e->getMessage());
			$this->view->assign('errors', array('Unable to store shared expense'));
			$this->render('view', 'sheets');
		}
		$this->redirect('/sheets/view/id/'.$this->getRequest()->getParam('id_sheet'));
	}
	
	public function closeAction() {
		try {
			$sheet = $this->getSheet();
			$this->sheetModel->update(array('closed_at' => date('Y-m-d H:i:s')), 'unique_id = "'.$sheet['unique_id'].'"');
			// @todo: set message for view "Sheet closed"
			// @todo: send email to all users in the sheet - sheet closed w/user that closed it.
			$this->view->assign('messages', array('Closed successfully'));
		}
		catch(Exception $e) {
			// return error 404
			// @todo: set error message
			$this->view->assign('errors', array($e->getMessage()));
		}
		$sheet = $this->sheetModel->get_by_unique_id($sheet['unique_id']);
		$this->view->assign('sheet', $sheet);
		$this->renderScript('sheets/view.phtml');
	}
	
	/**
	 * Copy Moxies to user.
	 */
	public function copyAction() {
		$cat_id = $this->getRequest()->getParam('id_category');
		$sheet_id = $this->getRequest()->getParam('id_sheet');
		// @todo validate user
		if(empty($_SESSION['user_id'])) {
			// @todo set error message
			error_log("error in session + category");
			$this->redirect('/sheets/view/id/'.$sheet_id);
		}
		// validate that category belongs to user
		$catModel = new Categories();
		try {
			$cat = $catModel->fetchRow("id = ".$cat_id)->toArray();
			if(empty($cat)) {
				throw new Exception("Category does not exists");
			}
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			throw new Exception("Category does not exists");
		}
		error_log(print_r($cat),true);
 		if ($cat['user_owner'] != $_SESSION['user_id']) {
			error_log("category does not belong to user ".$_SESSION['user_id']);
			throw new Exception("Category does not belong to user");
		}
		error_log("about to copy");
		// @todo copy only moxies for current user
		
		$sheet = $this->getSheet();
		
		// find sheet_user_id for this sheet and for this user
		$id_sheet_user = null;
		foreach($sheet['users'] as $u) {
			if($u['id_user'] == $_SESSION['user_id']) {
				$id_sheet_user = $u['id_sheet_user'];
				break;
			}
		}
		error_log("user is ".$id_sheet_user);
		if(is_null($id_sheet_user)) {
			throw new Exception("Id sheet user not found");
		}
		$sharedExpenses = new SharedExpenses();
		$expenses = new Expenses();
		foreach($sheet['expenses'] as $e) {
			if($e['id_sheet_user'] == $id_sheet_user) {
				// add expense with category received
				$expenses->insert(array(
						'user_owner' => $_SESSION['user_id'],
						'amount' => -$e['amount'],
						'category' => $cat['id'],
						'note' => $e['note'],
						'date' => $e['date'],
				));
				error_log("added expense, date ".$e['date']);
				// update closed
				$sharedExpenses->update(array('copied' => 1), 'id = '.$e['id']);
				error_log("shared expense closed");
			}
		}
		$this->redirect('/expenses');
		
	}
	
	public function adduserAction() {
		try {
			$unique_id = $this->getRequest()->getParam('id_sheet');
			$sheetUser = new SharedExpensesSheetUsers();
			$userModel = new Users();
			$sheet = $this->sheetModel->get_by_unique_id($unique_id);
			if(empty($sheet)) {
				throw new Exception("Sheet with id ".$unique_id." not found");
			}
			$user = $this->getRequest()->getParam('user');
			$user_id = null;
			$email = null;
			try {
				$u = $userModel->findUserByLogin($user);
				error_log(print_r($u,true));
				if(empty($u)) {
					error_log("user not found by login");
					$u = $userModel->findUserByEmail($user);
					error_log(print_r($u,true));
					if(empty($u)) {
						error_log("user not found by email");
						// @todo: check $user is a valid email. If not, raise
						error_log("settings user as email ".$user);
						$email = $user;
					}
				}
				if (!empty($u)) {
					error_log("\$u is set, ".print_r($u,true));
					$user_id = $u['id'];
					$email = $u['email'];
				}				
				$data = array(
					'id_sheet' => $sheet['id'],
					'id_user' => $user_id,
					'email' => $email
				);
				// @todo control duplicates
				$sheetUser->insert($data);
			}
			catch(Exception $e) {
				error_log("exception caught when adding user to sheet: ".$e->getMessage());
			}
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			$this->view->assign("errors", array($e->getMessage()));
		}
		$this->redirect('/sheets/view/id/'.$this->getRequest()->getParam('id_sheet'));
	}
	
	private function getSheet() {
		$id_sheet = $this->getRequest()->getParam('id_sheet', null);
		return $this->sheetModel->get_by_unique_id($id_sheet);
	}
	
	/**
	 * This function generates the form to add expenses.
	 * @param array $st_expense
	 * @return Zend_Form
	 */
	private function getForm($st_users, $id_sheet) {
		global $st_lang;
		$form  = new Zend_Form();
		
		// 		if(empty($st_expense['id'])) {
		// 			$in_sum_value = 1;
		// 			$slug = '/expenses/add';
		
		// 		}
		// 		else {
		// 			$in_sum_value = $st_expense['in_sum'];
		// 			$slug = '/expenses/update';
		// 		}
		
		// $form->setAction(Zend_Registry::get('config')->moxie->settings->url.$slug)->setMethod('post');
		
		// $form->setAttrib('id', 'login');
		
		// mount users list
		$users = array();
		foreach($st_users as $u) {
			$users[$u['id_sheet_user']] = $u['login'];
		}
		$form->addElement('text', 'amount', array('label' => "Importe", 'value' => 0, 'placeholder' => '0,00'));
		$multiOptions = new Zend_Form_Element_Select('user');
		$multiOptions->setName('id_sheet_user');
		$multiOptions->setLabel("Usuario");
		$multiOptions->addMultiOptions($st_users);
		//$multiOptions->setValue(array($st_expense['category']));
		$form->addElement($multiOptions);
		
		$form->addElement('text', 'note', array('label' => "Nota", 'value' => ''));
		$form->addElement('date', 'date', array('label' => "Fecha", 'value' => date('Y-m-d')));
		$form->addElement('submit','submit', array('label' => "Agregar"));
		$form->addElement('hidden', 'id_sheet', array('label' => null, 'value' => $id_sheet));
		return $form;
	}
}