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
		$form  = new Zend_Form();
		$categories = new Categories();
		
		// get categories
		$s_categories = $categories->getCategoriesByUser(1);
		foreach($s_categories as $key => $value) {
			$formCategories[$value['id1']] = $value['name1'].' - '.$value['name2'];
		}
		
		$form->setAction('/expenses/add')
		     ->setMethod('post');
		     
		$form->setAttrib('id', 'login');
		
		$form->addElement('hidden', 'user_owner', array('value' => '1'));
		$form->addElement('text', 'amount', array('label' => 'Amount', 'value' => '0.00'));
		$form->addElement('select', 'category', array(
			'label' => 'Category name',
			'multioptions' => $formCategories		
			)
		);
		$form->addElement('text', 'note', array('label' => 'Note'));
		$form->addElement('text', 'date', array('label' => 'Date', 'value' => date('Y-m-d')));
		$form->addElement('submit','submit', array('label' => 'Enviar'));
		return $form;
	}
	
	/**
	 * @desc	Shows the expenses view
	 * @author	hmeza
	 * @since	2011-01-03
	 */
	public function indexAction() {
		// list current month by default
		// allow navigate through months and years
		$i_month = $this->getRequest()->getParam('month');
		$i_year = $this->getRequest()->getParam('year');
		$i_month = (isset($i_month)) ? $this->getRequest()->getParam('month') : date('n');
		$i_year = (isset($i_year)) ? $this->getRequest()->getParam('year') : date('Y');
		$this->view->assign('list', $this->expenses->getExpenses(1,$i_month,$i_year));
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
		$this->expenses->addExpense(1,$st_form['date'],$st_form['amount'],$st_form['category'],$st_form['note']);
		$this->_helper->redirector('index','expenses');
	}
	
	// TODO
	public function editAction() {

	}
	
	/**
	 * @desc	Deletes a given action
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function deleteAction() {
		// TODO: Check authenticaded user
		// TODO: Check that expense belongs to user
		$i_expensePK = $this->getRequest()->getParam('id');
		$this->expenses->deleteExpense($i_expensePK);
		$this->_helper->redirector('index','expenses');
	}
	
	//TODO
	public function marklineAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		$this->expenses->updateExpense($i_expensePK);
		$this->_helper->redirector('index','expenses');
	}
}