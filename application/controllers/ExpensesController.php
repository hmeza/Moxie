<?php
/** Zend_Controller_Action */
class ExpensesController extends Zend_Controller_Action
{
	/** @var Expenses */
	private $expenses;
	/** @var Budgets */
	private $budgets;
	/** @var Tags */
	private $tags;
	/** @var TransactionTags */
	private $transactionTags;

	public function init() {
		parent::init();
		$this->expenses = new Expenses();
		$this->budgets = new Budgets();
		$this->tags = new Tags();
		$this->transactionTags = new TransactionTags();
	}
	
	/**
	 * This function generates the form to add expenses.
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	private function getAddForm() {
		global $st_lang;
		
		$form  = new Zend_Form();
		$categories = new Categories();
		
		$form->setAction(Zend_Registry::get('config')->moxie->settings->url.'/expenses/add')
		     ->setMethod('post');
		     
		$form->setAttrib('id', 'login');
		$st_categories = $categories->getCategoriesForView(Categories::EXPENSES);
		asort($st_categories);

		$form->addElement('text', 'amount', array('label' => $st_lang['expenses_amount'], 'value' => '0.00'));
		$form->addElement('select', 'category', array(
			'label' => $st_lang['expenses_category'],
			'multioptions' => $st_categories
			)
		);
		$form->addElement('text', 'note', array('label' => $st_lang['expenses_note']));
		$form->addElement('text', 'date', array('label' => $st_lang['expenses_date'], 'value' => date('Y-m-d')));
		$form->addElement('submit','submit', array('label' => $st_lang['expenses_send']));
		return $form;
	}
	
	/**
	 * This function generates the form to add expenses.
	 * @author	hmeza
	 * @since	2011-01-30
	 * @param	int $i_expensePK
	 */
	private function getEditForm($i_expensePK) {
		global $st_lang;
		$form  = new Zend_Form();
		$categories = new Categories();
		
		// retrieve data to fill the form
		$st_expense = $this->expenses->getExpenseByPK($i_expensePK);
		// little fix to pass only date and discarding hour
		$s_date = explode(" ", $st_expense['date']);
		$st_expense['date'] = $s_date[0];
		
		$form->setAction(Zend_Registry::get('config')->moxie->settings->url.'/expenses/update')
		     ->setMethod('post');
		     
		$form->setAttrib('id', 'login');

		$form->addElement('hidden', 'checked', array('value' => $st_expense['in_sum']));
		$form->addElement('hidden', 'user_owner', array('value' => $st_expense['user_owner']));
		$form->addElement('hidden', 'id', array('value' => $i_expensePK));
		$form->addElement('text', 'amount', array('label' => $st_lang['expenses_amount'], 'value' => $st_expense['amount']));
		// Add select
		$multiOptions = new Zend_Form_Element_Select('category', $categories->getCategoriesForView(Categories::EXPENSES));
		$multiOptions->setLabel($st_lang['expenses_category']);
		$st_categories = $categories->getCategoriesForView(Categories::EXPENSES);
		asort($st_categories);
		$multiOptions->addMultiOptions($st_categories);
		$multiOptions->setValue(array($st_expense['category']));
		$form->addElement($multiOptions);
		$form->addElement('text', 'note', array('label' => $st_lang['expenses_note'], 'value' => $st_expense['note']));
		$form->addElement('text', 'date', array('label' => $st_lang['expenses_date'], 'value' => $st_expense['date']));
		$form->addElement('submit','submit', array('label' => $st_lang['expenses_send']));
		return $form;
	}
	
	/**
	 * Returns the monthly expense for a year.
	 * @return array
	 */
	private function getMonthExpensesData() {
		$st_data = array();
		$i_dateLimit = date("Y-m-01 00:00:00", strtotime("-12 months"));
		
		$s_category = (!empty($category)) ? 'category = '.$category : '1=1';

        $o_rows = $this->expenses->getMonthExpensesData($_SESSION['user_id'], $i_dateLimit, $s_category);

		foreach ($o_rows as $key => $value) {
			$timestamp = mktime(0, 0, 0, $value['month'], 1, $value['year']);
			$st_data[] = array(
					date("M", $timestamp),
					(float)$value['amount']
			);
		}
		$st_data = array_merge(array(array('Month', 'Expense')), $st_data);				
		return $st_data;
	}
	
	/**
	 * Return the monthly expense for a year to the view.
	 * @return string
	 */
	public function yearAction() {
		$this->getResponse()->setHeader('Content-type', 'text/plain')
							->setHeader('Cache-Control','no-cache');
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout()->disableLayout();
		echo json_encode($this->getMonthExpensesData());
		exit();
	}
	
	/**
	 * Return the month expense.
	 * @return string
	 */
	public function monthAction() {
		$this->getResponse()->setHeader('Content-type', 'text/plain')
							->setHeader('Cache-Control','no-cache');
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout()->disableLayout();
		
		$db = Zend_Registry::get('db');
		$i_month = $this->getRequest()->getParam('month');
		$i_year = $this->getRequest()->getParam('year');
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
				->where('YEAR(e.date) = '.$i_year)
				->where('MONTH(e.date) = '.$i_month)
				->where('e.in_sum = 1')
				->group('c.id')
				->order(array('c.id'));
		$st_data = $db->fetchAll($s_select);
		$st_response = array();
		foreach($st_data as $st_row) {
			$st_response[] = array($st_row['name'], (float)$st_row['sum(e.amount)']);
		}
		echo json_encode($st_response);
		exit();
	}
	
	/**
	 * Shows the expenses view.
	 */
	public function indexAction() {
		global $st_lang;
		
		// list current month by default
		// allow navigate through months and years
		$i_month = $this->getRequest()->getParam('month');
		$i_year = $this->getRequest()->getParam('year');
		$i_category = $this->getRequest()->getParam('category_filter');
		$i_category = (isset($i_category)) ? $i_category : 0;
		$s_tag = $this->getRequest()->getParam('tag_filter');
		$s_tag = (isset($s_tag) ? $s_tag : null);
		$s_toExcel  = $this->getRequest()->getParam('to_excel');
		$i_month = (isset($i_month)) ? $this->getRequest()->getParam('month') : date('n');
		$i_year = (isset($i_year)) ? $this->getRequest()->getParam('year') : date('Y');

        try {
            $st_data = $this->expenses->getExpensesForIndex($_SESSION['user_id'], $i_month, $i_year);
			if((empty($i_category) && empty($s_tag)) || !empty($i_category)) {
				$st_list = $this->expenses->getExpenses($_SESSION['user_id'], $i_month, $i_year, $i_category);
			}
	        else {
		        $st_list = $this->expenses->getTaggedExpenses($_SESSION['user_id'], $i_month, $i_year, $s_tag);
	        }
        }
        catch(Exception $e) {
            throw new Exception("Error recovering expenses");
        }

		if($s_toExcel == true) {
			$this->exportToExcel($st_list);
		}
		
		$this->view->assign('expenses', $st_data);
		$this->view->assign('expenses_label', $st_lang['expenses_monthly']);
		$this->view->assign('month_expenses', json_encode($this->getMonthExpensesData()));
		$this->view->assign('month_expenses_label', $st_lang['expenses_by_months']);
		$this->view->assign('budget', $this->budgets->getBudget($_SESSION['user_id']));
		$this->view->assign('list', $st_list);
		$this->view->assign('year', $i_year);
		$this->view->assign('month', $i_month);
		$this->view->assign('form', $this->getAddForm());
		$this->view->assign('tag_list', $this->tags->getTagsByUser($_SESSION['user_id']));
	}

	/**
	 * Exports to excel the data currently shown in the view.
	 * @param array|array[] $st_data containing rows with columns date, amount, name and note.
	 */
	private function exportToExcel($st_data) {
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename='.date('Y-m-d').'.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array('Fecha', 'Euros', 'Categoria', 'Nota'));

		foreach($st_data as $row) {
			$outputRow = array(
				'Fecha' => $row['date'],
				'Euros' => $row['amount'],
				'Categoria' => $row['name'],
				'Nota' => $row['note']
			);
			fputcsv($output, $outputRow);
		}
		exit(0);
	}

	/**
	 * @param array $tags
	 * @param int $expenseId
	 * @throws Exception
	 */
	private function updateTags($tags, $expenseId) {
		$existingTags = $this->tags->getTagsByUser($_SESSION['user_id']);
		foreach($tags as $tag) {
			$tagId = array_search($tag, $existingTags);
			if($tagId === FALSE) {
				$tagId = $this->tags->addTag($_SESSION['user_id'], $tag);
			}
			$this->transactionTags->addTagToTransaction($expenseId, $tagId);
		}
	}
	
	/**
	 * Adds an expense and shows expenses index again
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function addAction() {
		$st_form = $this->getRequest()->getPost();
		$st_form['amount'] = str_replace(",",".",$st_form['amount']);
		if (empty($st_form['note'])) $st_form['note'] = "";
		if (!isset($st_form['category'])) $st_form['category'] = 10;
		$st_form['date'] = str_replace('/', '-', $st_form['date']);
		$expenseId = $this->expenses->addExpense($_SESSION['user_id'],$st_form['date'],$st_form['amount'],$st_form['category'],$st_form['note']);
		if(!empty($_POST['taggles'])) {
			$this->updateTags($_POST['taggles'], $expenseId);
		}
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * Edits a given expense
	 * @author	hmeza
	 * @since	2011-02-08
	 */
	public function editAction() {
		global $st_lang;
		
		$i_expensePK = $this->getRequest()->getParam('id');
		$i_month = $this->getRequest()->getParam('month');
		$i_year = $this->getRequest()->getParam('year');
		$i_month = (isset($i_month)) ? $this->getRequest()->getParam('month') : date('n');
		$i_year = (isset($i_year)) ? $this->getRequest()->getParam('year') : date('Y');
		
		$st_data = $this->expenses->getExpensesForEdit($_SESSION['user_id'], $i_month, $i_year);
		
		$this->view->assign('expenses', $st_data);
		$this->view->assign('expenses_label', $st_lang['expenses_monthly']);
		$this->view->assign('month_expenses', json_encode($this->getMonthExpensesData()));
		$this->view->assign('month_expenses_label', $st_lang['expenses_by_months']);
		$this->view->assign('budget', $this->budgets->getBudget($_SESSION['user_id']));
		$this->view->assign('list', $this->expenses->getExpenses($_SESSION['user_id'],$i_month,$i_year));
		$this->view->assign('year', $i_year);
		$this->view->assign('month', $i_month);
		$this->view->assign('form', $this->getEditForm($i_expensePK));
		$this->view->assign('tags', $this->transactionTags->getTagsForTransaction($i_expensePK));
		$this->view->assign('tag_list', $this->tags->getTagsByUser($_SESSION['user_id']));
		$this->render('index');
	}
	
	/**
	 * Updates an expense
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function updateAction() {
		$st_params = $this->getRequest()->getPost();
		$i_expensePK = $st_params['id'];

		$this->transactionTags->removeTagsFromTransaction($i_expensePK);
		if(!empty($_POST['taggles'])) {
			$this->updateTags($_POST['taggles'], $i_expensePK);
		}

		$this->expenses->updateExpense($i_expensePK, $st_params);
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * Deletes a given expense
	 * @author	hmeza
	 * @since	2011-01-30
	 */
	public function deleteAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		try {
			$this->transactionTags->removeTagsFromTransaction($i_expensePK);
			$this->expenses->delete('id = '.$i_expensePK.' AND user_owner = '.$_SESSION['user_id']);
		} catch (Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
		}
		$this->_helper->redirector('index','expenses');
	}

	/**
	 * Marks an expense to appear or not in sums
	 * @author	hmeza
	 * @since	2011-02-03
	 */
	public function marklineAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		$this->expenses->updateExpense($i_expensePK);
		$this->_helper->redirector('index','expenses');
	}
}
