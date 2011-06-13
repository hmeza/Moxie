<?php
/** Zend_Controller_Action */
include 'application/models/Expenses.php';
class ExpensesController extends Zend_Controller_Action
{
	private $expenses;
	
	public function init() {
		$this->expenses = new Expenses();		
	}
	
	/**
	 * @desc	This function generates the form to add expenses.
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	private function getAddForm() {
		include('Zend/Form.php');
		include('application/models/Categories.php');
		global $st_lang;
		
		$form  = new Zend_Form();
		$categories = new Categories();
		
		$form->setAction('/expenses/add')
		     ->setMethod('post');
		     
		$form->setAttrib('id', 'login');

		$form->addElement('text', 'amount', array('label' => $st_lang['expenses_amount'], 'value' => '0.00'));
		$form->addElement('select', 'category', array(
			'label' => $st_lang['expenses_category'],
			'multioptions' => $categories->getCategoriesForView(1)
			)
		);
		$form->addElement('text', 'note', array('label' => $st_lang['expenses_note']));
		$form->addElement('text', 'date', array('label' => $st_lang['expenses_date'], 'value' => date('Y-m-d')));
		$form->addElement('submit','submit', array('label' => $st_lang['expenses_send']));
		return $form;
	}
	
	/**
	 * @desc	This function generates the form to add expenses.
	 * @author	hmeza
	 * @since	2011-01-30
	 * @param	int $i_expensePK
	 */
	private function getEditForm($i_expensePK) {
		include('Zend/Form.php');
		include('Zend/Form/Element/Select.php');
		include('application/models/Categories.php');
		$form  = new Zend_Form();
		$categories = new Categories();
		
		// retrieve data to fill the form
		$st_expense = $this->expenses->getExpenseByPK($i_expensePK);
		// little fix to pass only date and discarding hour
		$s_date = explode(" ", $st_expense['expense_date']);
		$st_expense['expense_date'] = $s_date[0];
		
		$form->setAction('/expenses/update')
		     ->setMethod('post');
		     
		$form->setAttrib('id', 'login');
		
		$form->addElement('hidden', 'user_owner', array('value' => $st_expense['user_owner']));
		$form->addElement('hidden', 'id', array('value' => $i_expensePK));
		$form->addElement('text', 'amount', array('label' => 'Amount', 'value' => $st_expense['amount']));
		// Add select
		$multiOptions = new Zend_Form_Element_Select('category', $categories->getCategoriesForView(Categories::EXPENSES));
		$multiOptions->addMultiOptions($categories->getCategoriesForView(Categories::EXPENSES));
		$multiOptions->setValue(array($st_expense['category']));
		$form->addElement($multiOptions);
		$form->addElement('text', 'note', array('label' => 'Note', 'value' => $st_expense['note']));
		$form->addElement('text', 'date', array('label' => 'Date', 'value' => $st_expense['expense_date']));
		$form->addElement('submit','submit', array('label' => 'Enviar'));
		return $form;
	}
	
	/**
	 * @desc	Shows the expenses view
	 * @author	hmeza
	 * @since	2011-01-03
	 */
	public function indexAction() {
		include 'application/models/Budgets.php';
		$o_budget = new Budgets();
		$st_data = $o_budget->fetchAll(
						$o_budget->select()->where('user_owner = '.$_SESSION['user_id'])
					);
		$st_budget = array();
		foreach($st_data as $key => $value) {
			$st_budget[$value['category']] = $value['amount'];
		}
		
		// list current month by default
		// allow navigate through months and years
		$i_month = $this->getRequest()->getParam('month');
		$i_year = $this->getRequest()->getParam('year');
		$i_category = $this->getRequest()->getParam('category_filter');
		$i_category = (isset($i_category)) ? $i_category : 0;
		$i_month = (isset($i_month)) ? $this->getRequest()->getParam('month') : date('n');
		$i_year = (isset($i_year)) ? $this->getRequest()->getParam('year') : date('Y');
		
		$db = Zend_Registry::get('db');
                $s_select = $db->select()
                        ->from(array('e'=>'expenses'),
                                        array(
                                                'sum(e.amount)' =>      'sum(e.amount)'
                                        ))
                        ->joinLeft(array('c'=>'categories'),'e.category = c.id', array(
                                                'id'            =>      'c.id',
                                                'name'          =>      'c.name'
                                        ))
                        ->where('e.user_owner = '.$_SESSION['user_id'])
                        ->where('YEAR(e.expense_date) = '.$i_year)
                        ->where('MONTH(e.expense_date) = '.$i_month)
                        ->where('e.in_sum = 1')
                        ->group('c.id')
                        ->order(array('c.id'));
		$st_data = $db->fetchAll($s_select);
		
		$this->view->assign('expenses', $st_data);
		$this->view->assign('budget', $st_budget);
		$this->view->assign('list', $this->expenses->getExpenses($_SESSION['user_id'],$i_month,$i_year,$i_category));
		$this->view->assign('year', $i_year);
		$this->view->assign('month', $i_month);
		$this->view->assign('form', $this->getAddForm());
	}
	
	/**
	 * @desc	Adds an expense and shows expenses index again
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function addAction() {
		$st_form = $this->getRequest()->getPost();
		$st_form['amount'] = str_replace(",",".",$st_form['amount']);
		if (!isset($st_form['note'])) $st_form['note'] = "";
		if (!isset($st_form['category'])) $st_form['category'] = 10;
		$st_form['date'] = str_replace('/', '-', $st_form['date']);
		$this->expenses->addExpense($_SESSION['user_id'],$st_form['date'],$st_form['amount'],$st_form['category'],$st_form['note']);
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * @desc	Edits a given expense
	 * @author	hmeza
	 * @since	2011-02-08
	 */
	public function editAction() {
		include 'application/models/Budgets.php';
		$o_budget = new Budgets();
		$st_data = $o_budget->fetchAll(
						$o_budget->select()->where('user_owner = '.$_SESSION['user_id'])
					);
		$st_budget = array();
		foreach($st_data as $key => $value) {
			$st_budget[$value['category']] = $value['amount'];
		}
		
		$i_expensePK = $this->getRequest()->getParam('id');
		$i_month = $this->getRequest()->getParam('month');
		$i_year = $this->getRequest()->getParam('year');
		$i_month = (isset($i_month)) ? $this->getRequest()->getParam('month') : date('n');
		$i_year = (isset($i_year)) ? $this->getRequest()->getParam('year') : date('Y');
		
		$db = Zend_Registry::get('db');
		$s_select = $db->select()
			->from(array('e'=>'expenses'),
					array(
						'sum(e.amount)'	=>	'sum(e.amount)'
					))
			->join(array('c'=>'categories'),'',array(
						'id'		=>	'c.id',
						'name'		=>	'c.name'
					))
			->joinLeft(array('c2'=>'categories'),'c.id = c2.parent',
					array(
						'son_id'	=>	'c2.id'
					))
			->joinLeft(array('c0'=>'categories'),'c0.id = c.parent',
					array(
						'parent_id'	=>	'c0.id'
					))
			->where('e.user_owner = '.$_SESSION['user_id'])
			->where('c.id = e.category OR c2.id = e.category')
			->where('YEAR(e.expense_date) = '.$i_year)
			->where('MONTH(e.expense_date) = '.$i_month)
			->where('e.in_sum = 1')
			->group('c.id')
			->order(array('c.id','c2.id'));
		$st_data = $db->fetchAll($s_select);
		
		$this->view->assign('expenses', $st_data);
		$this->view->assign('budget', $st_budget);
		$this->view->assign('list', $this->expenses->getExpenses($_SESSION['user_id'],$i_month,$i_year));
		$this->view->assign('year', $i_year);
		$this->view->assign('month', $i_month);
		$this->view->assign('form', $this->getEditForm($i_expensePK));
		$this->render('index');
	}
	
	public function updateAction() {
		$st_params = $this->getRequest()->getPost();
		$i_expensePK = $st_params['id'];
		$this->expenses->updateExpense($i_expensePK, $st_params);
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * @desc	Deletes a given expense
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function deleteAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		try {
			$this->expenses->delete('id = '.$i_expensePK.' AND user_owner = '.$_SESSION['user_id']);
		} catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * @desc
	 * @author	hmeza
	 * @since	2011-02-06
	 * @todo	Select year and month by sent parameters
	 */
	public function markallAction() {
		$i_option = $this->getRequest()->getParam('option');
		$i_year = $this->getRequest()->getParam('year');
		$i_month = $this->getRequest()->getParam('month');
		$this->expenses->updateAllExpenses($_SESSION['user_id'], $i_option, $i_year, $i_month);
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * @desc	Marks an expense to appear or not in sums
	 * @author	hmeza
	 * @since	2011-02-03
	 */
	public function marklineAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		$this->expenses->updateExpense($i_expensePK);
		$this->_helper->redirector('index','expenses');
	}
}
