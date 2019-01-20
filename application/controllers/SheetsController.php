<?php
/** Zend_Controller_Action */
class SheetsController extends Zend_Controller_Action
{
    /**
     * @var SharedExpensesSheet
     */
	private $sheetModel;
	
	public function init() {
		$this->sheetModel = new SharedExpensesSheet();
	}
	
	/**
	 * Show sheets page and all my sheets.
	 */
	public function indexAction() {
		$this->set_sheet_list_to_view();
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
		try {
			$this->view->assign('categories', $categories->getCategoriesForView(Categories::EXPENSES));
		}
		catch(Exception $e) {
			$this->view->assign('categories', array());
		}
		$this->set_sheet_list_to_view();
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
                    'unique_id' => uniqid(),
                    'currency' => $this->getRequest()->getParam('currency', SharedExpenses::DEFAULT_CURRENCY),
                    'change' => str_replace(",", ".", $this->getRequest()->getParam('change', 1))
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
		global $st_lang;
		$id_sheet = $this->getRequest()->getParam('id');
		// validations: logged user
		if(!isset($_SESSION) || (isset($_SESSION) && empty($_SESSION['user_id']))) {
			// return 403
			$this->_request->setPost(array(
					'id' => $id_sheet,
					'errors' => array($st_lang['error_nouser'])
			));
			return $this->_forward("view", "sheets");
		}
		try  {
			$sheet = $this->getSheet();
		}
		catch(Exception $e) {
			// return 404
			$this->view->assign('errors', array('Sheet not found'));
			//$this->render('index', 'expenses');
			$this->redirect('/sheets/view/id/'.$id_sheet);
		}
		try {
			$sharedExpenseModel = new SharedExpenses();
            $amount = str_replace(",",".",$this->getRequest()->getParam('amount'));
            $currency = $this->getRequest()->getParam('currency');
            $currencyValue = ($currency === "on") ? $sheet['currency'] : SharedExpenses::DEFAULT_CURRENCY;
			$data = array(
					'id_sheet' => $sheet['id'],
					'id_sheet_user' => $this->getRequest()->getParam('id_sheet_user'),
					'amount' => $amount,
					'note' => $this->getRequest()->getParam('note', ''),
					'date' => $this->getRequest()->getParam('date'),
                    'currency' => $currencyValue
			);
			$sharedExpenseModel->insert($data);
		}
		catch(Exception $e) {
			// return 500 / error message
			error_log($e->getMessage());
			$this->view->assign('errors', array('Unable to store shared expense'));
			$this->render('view', 'sheets');
		}
		$this->redirect('/sheets/view/id/'.$id_sheet);
	}
	
	public function deleteAction() {
	    global $st_lang;
		// validations: logged user
		if(!isset($_SESSION) || (isset($_SESSION) && empty($_SESSION['user_id']))) {
			// return 403
			$this->_request->setPost(array(
					'id' => $this->getRequest()->getParam('id'),
					'errors' => array($st_lang['error_nouser'])
			));
			return $this->_forward("view", "sheets");
		}
		try {
			$seid = $this->getRequest()->getParam('id');
			// validate that current user appears in the sheet of this shared expense
			$seModel = new SharedExpenses();
			$seModel->find($seid);
			$row = $seModel->getSheetByExpenseIdAndUserId($seid, $_SESSION['user_id']);
			if(empty($row)) {
				throw new Exception("Shared expense does not appear in a sheet from current user");
			}
			$id_sheet = $row['unique_id'];
			$seModel->delete('id = '.$seid);
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			$this->redirect('/sheets');
		}
		$this->redirect('/sheets/view/id/'.$id_sheet);
	}
	
	public function closeAction() {
		global $st_lang;
		$id_sheet = $this->getRequest()->getParam('id_sheet', null);
		// validations: logged user
		if(!isset($_SESSION) || (isset($_SESSION) && empty($_SESSION['user_id']))) {
			// return 403
			$this->_request->setPost(array(
					'id' => $id_sheet,
					'errors' => array($st_lang['error_nouser'])
			));
			return $this->_forward("view", "sheets");
		}
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
		$this->redirect('/sheets/view/id/'.$sheet['unique_id']);
	}
	
	/**
	 * Copy Moxies to user.
	 */
	public function copyAction() {
		$sheet_id = $this->getRequest()->getParam('id_sheet');
		// @todo validate user
		if(empty($_SESSION['user_id'])) {
			// @todo set error message
			error_log("error in session + category");
			$this->redirect('/sheets/view/id/'.$sheet_id);
		}
		// validate that category belongs to user
		$catModel = new Categories();

		$sheet = $this->getSheet();

		// Default change rate is 1, so if change is not set from the form, 1 will be used
		$changeRate = $this->getParam('change', $sheet['change']);
		
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
		foreach($_POST['row'] as $row) {
		    if(empty($row['category_id'])) {
		        continue;
            }
		    // sanity check
            $found = false;
            $e = null;
            foreach ($sheet['expenses'] as $e) {
                if ($e['id'] == $row['id']) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                continue;
            }
            try {
                $cat = $catModel->fetchRow("id = ".$row['category_id'])->toArray();
                if(empty($cat)) {
                    throw new Exception("Category does not exists");
                }
            }
            catch(Exception $e) {
                error_log($e->getMessage());
                throw new Exception("Category does not exists");
            }
            if ($cat['user_owner'] != $_SESSION['user_id']) {
                error_log("category does not belong to user ".$_SESSION['user_id']);
                throw new Exception("Category does not belong to user");
            }
            // add expense with category received
            $expenses->insert(array(
                'user_owner' => $_SESSION['user_id'],
                'amount' => -$e['amount'] / $changeRate,
                'category' => $row['category_id'],
                'note' => $e['note'],
                'date' => $e['date'],
            ));
            // update closed
            $sharedExpenses->update(array('copied' => 1), 'id = ' . $e['id']);
        }
		$this->redirect('/expenses');

	}
	
	public function adduserAction() {
		try {
			$id_sheet= $this->getRequest()->getParam('id_sheet');
			$sheetUser = new SharedExpensesSheetUsers();
			$userModel = new Users();
			$sheet = $this->sheetModel->get_by_unique_id($id_sheet);
			if(empty($sheet)) {
				throw new Exception("Sheet with id ".$id_sheet." not found");
			}
			$user = $this->getRequest()->getParam('user');
			$user_id = null;
			$email = null;
			$registered = true;
			try {
				$u = $userModel->findUserByLogin($user);
				error_log(print_r($u,true));
				if(empty($u)) {
					error_log("user not found by login");
					$u = $userModel->findUserByEmail($user);
					error_log(print_r($u,true));
					if(empty($u)) {
						error_log("user not found by email");
						$validator = new Zend_Validate_EmailAddress();
						if (!$validator->isValid($email)) {
							throw new Exception("Invalid email address");
						}
						error_log("settings user as email ".$user);
						$email = $user;
						$registered = false;
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
				$this->sendUserAdded($id_sheet, $email, $sheet['name'], $registered);
			}
			catch(Exception $e) {
				error_log("exception caught when adding user to sheet: ".$e->getMessage());
				$this->_request->setPost(array(
						'id' => $id_sheet,
						'errors' => array($e->getMessage())
				));
				return $this->_forward("view", "sheets");
			}
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			$this->view->assign("errors", array($e->getMessage()));
		}
		$this->redirect('/sheets/view/id/'.$id_sheet);
	}
	
	private function getSheet() {
		$id_sheet = $this->getRequest()->getParam('id_sheet', null);
                if(empty($id_sheet)) {
                        $id_sheet = $this->getRequest()->getParam('id', null);
                }
		return $this->sheetModel->get_by_unique_id($id_sheet);
	}
	
	/**
	 * This function generates the form to add expenses.
	 * @param array $st_users
     * @param int $id_sheet
	 * @return Zend_Form
	 */
	private function getForm($st_users, $id_sheet) {
		global $st_lang;
		$form  = new Zend_Form();
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
		$form->addElement($multiOptions);
		
		$form->addElement('text', 'note', array('label' => "Nota", 'value' => ''));
		$form->addElement('date', 'date', array('label' => "Fecha", 'value' => date('Y-m-d')));
		$form->addElement('submit','submit', array('label' => "Agregar"));
		$form->addElement('hidden', 'id_sheet', array('label' => null, 'value' => $id_sheet));
		return $form;
	}
	
	private function set_sheet_list_to_view() {
		try {
			if (empty($_SESSION['user_id'])) {
				throw new Exception("Empty user id");
			}
			$sheets = $this->sheetModel->get_by_user_match($_SESSION['user_id']);
			$this->view->assign('sheet_list', $sheets);
			$this->view->assign('sheet_list_form', $this->getSheetSelector($sheets));
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			$this->view->assign('sheet_list', array());
			$this->view->assign('sheet_list_form', new Zend_Form());
		}
	}
	
	private function getSheetSelector($sheets) {
		global $st_lang;
		$form  = new Zend_Form();
		
		$sheet_list = array('-' => '---');
		foreach($sheets as $s) {
			$sheet_list[$s['unique_id']] = $s['name'];
		}
		$multiOptions = new Zend_Form_Element_Select('sheet_id_redirector');
		$multiOptions->setName('sheet_id_redirector');
		$multiOptions->setLabel($st_lang['sheets_select_sheet']);
		$multiOptions->addMultiOptions($sheet_list);
		$multiOptions->setAttrib('onchange', 'redirect()');
		$multiOptions->setAttrib('class', 'form-control');
		$form->addElement($multiOptions);
		$form->setAttrib("id", "sheet_id_redirector_form");
		$form->setAttrib("name", "sheet_id_redirector_form");
		return $form;
	}
	
	/**
	 * Email user with register data.
	 * @param $i_lastInsertId
	 * @param $st_form
	 * @throws Zend_Db_Table_Exception
	 * @throws Zend_Exception
	 */
	private function sendUserAdded($sheetId, $userEmail, $sheetName, $registered=false) {
		global $st_lang;
		$s_server = Zend_Registry::get('config')->moxie->settings->url;
		$s_site = Zend_Registry::get('config')->moxie->app->name;
		$subject = $s_site . ' - '.$st_lang['sheets_email_subject'];
		$url = $s_server . '/sheets/view/id/'.$sheetId;
		$body = $st_lang['sheets_email_body_1'].'

'.$sheetName.'
'.$url.'

'.$st_lang['sheets_email_body_2'].'

'.$st_lang['emails_footer'];
		
		$headers = 'From: Moxie <moxie@dootic.com>' . "\r\n" .
				'Reply-To: moxie@dootic.com' . "\r\n" .
				'X-Mailer: PHP/' . phpversion() . "\r\n";
		$result = mail($userEmail, $subject, $body, $headers);
	}
}
