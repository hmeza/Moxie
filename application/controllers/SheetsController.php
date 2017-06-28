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
		$sheet = $this->sheetModel->get_by_unique_id(
				$this->getRequest()->getParam('id', null)
		);
		error_log($this->getRequest()->getParam('id'));
		error_log(print_r($sheet,true));
		$this->view->assign('sheet', $sheet);
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
		}
		catch(Exception $e) {
			// return error 404
		}
		// set message for view "Sheet closed"
		$this->_helper->redirector('index','shared_expenses');
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
			$data = array(
					'id_sheet' => $sheet['id'],
			);
			$op = $this->getRequest()->getParam('user_type');
			$user = $this->getRequest()->getParam('user');
			if ($op == "email") {
				$u = $userModel->findUserByEmail($user);
				if (isset($u)) {
					$data['id_user'] = $u['id'];
				}
				else {
					$data['id_user'] = null;
				}
				$data['email'] = $user;
			}
			else {
				$u = $userModel->findUserByLogin($user);
				error_log(print_r($u, true));
				if(empty($u)) {
					throw new Exception("User not found");
				}
				$data['id_user'] = $u['id'];
				$data['email'] = $u['email'];
			}
			// @todo control duplicates
			$sheetUser->insert($data);
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
}