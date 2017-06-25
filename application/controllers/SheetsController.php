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
						'name' => $_POST['name'],
						'unique_id' => uniqid()
				);
				$id = $this->sheetModel->insert($data);
				$sheet = $this->sheetModel->find($id)->current();
				$this->view->assign('sheet', $sheet);
			}
			catch(Exception $e) {
				$errors = array($e->getMessage());
				$this->view->assign('errors', $errors);
			}
			$this->_helper->redirector('view','sheets');
		}
		// else render GET page
	}
	
	public function addAction() {
		try  {
			$sheet = $this->getSheet();
		}
		catch(Exception $e) {
			// return 404
		}
		try {
			$sheed->addEntry(user_owner, amount, note, date);
		}
		catch(EXception $e) {
			// return 500 / error message
		}
		// @todo redirect to sheet
		$this->_helper->redirector('index','shared_expenses');
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
	
	private function getSheet() {
		if (empty($_POST['sheet_id'])) {
			// throw
		}
		$sheet = SharedExpensesSheet::find($_POST['sheet_id']);
	}
}