<?php
/** Zend_Controller_Action */
include 'application/models/Incomes.php';
class IncomesController extends Zend_Controller_Action
{
	private $incomes;
	
	public function init() {
		//$this->incomes = new Incomes();
		$this->incomes = new Incomes(array('name' => 'incomes', 'schema' => 'moxie'));	
	}
	
	/**
	 * @desc	This function generates the form to add incomes.
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	private function getAddForm() {
		include('Zend/Form.php');
		include('application/models/Categories.php');
		$form  = new Zend_Form();
		$categories = new Categories();
		
			// get categories and prepare them for view
		$s_categories = $categories->getCategoriesByUser(2);
		foreach($s_categories as $key => $value) {
			$formCategories[$value['id1']] = $value['name2'];
			if (!empty($value['name1'])) {
				$formCategories[$value['id1']] = $value['name1'].' - '.$formCategories[$value['id1']];
			}
		}
		
		$form->setAction('/incomes/add')
		     ->setMethod('post');
		     
		$form->setAttrib('id', 'login');
		
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
		// list current year and navigate through years
		$i_year = $this->getRequest()->getParam('year');
		$i_year = (isset($i_year)) ? $this->getRequest()->getParam('year') : date('Y');
		
		$list = array();
		// get categories
		for ($i=1;$i<=12;$i++) {
			$s_select = $this->incomes->select()
				->where('YEAR(date) = '.$i_year.' AND MONTH(date) = '.$i)
				->where('user_owner = '.$_SESSION['user_id']);
			$st_rows = $this->incomes->fetchAll($s_select);
			if (count($st_rows) > 0) {
				foreach($st_rows as $key => $value) {
					$list[$i][$key] =  $value;
				}
			}
		}
		$this->view->assign('list', $list);
		$this->view->assign('year', $i_year);
		$this->view->assign('form', $this->getAddForm());
	}
	
	/**
	 * @desc	Adds an expense and shows expenses index again
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function addAction() {
		$o_income = $this->getRequest()->getPost();
		$st_data = array(
			'user_owner'	=>	$_SESSION['user_id'],
			'amount'		=>	$o_income['amount'],
			'category'		=>	$o_income['category'],
			'note'			=>	$o_income['note'],
			'date'			=>	$o_income['date'],
			'in_sum'		=>	1
		);
		$this->incomes->insert($st_data);
		$this->_helper->redirector('index','incomes');
	}
	
	// TODO
	public function editAction() {
		include 'application/models/Budgets.php';
		$i_incomePK = $this->getRequest()->getParam('id');
		$this->incomes->find($i_incomePK);
		
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
	
	/**
	 * @desc	Deletes a given income
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function deleteAction() {
		$i_incomePK = $this->getRequest()->getParam('id');
		try {
			$this->incomes->delete('id = '.$i_incomePK.' and user_owner = '.$_SESSION['user_id']);
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
		$this->_helper->redirector('index','incomes');
	}
}