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
	/** @var Categories */
	private $categories;
	/** @var TransactionTags */
	private $transactionTags;
    /** @var int  */
    private $currentCategory;
	/** @var boolean */
	private $showTagsFilter = true;
	/** @var boolean */
	private $showCategoriesFilter = true;

	public function init() {
		parent::init();
		$this->expenses = new Expenses();
		$this->budgets = new Budgets();
		$this->tags = new Tags();
		$this->transactionTags = new TransactionTags();
		$this->categories = new Categories();
	}

	/**
	 * @param Zend_Controller_Request_Abstract $request
	 * @return Zend_Form
	 * @throws Zend_Exception
	 * @throws Zend_Form_Exception
	 */
	private function getSearchForm($request) {
		global $st_lang;
		$form  = new Zend_Form();
		$slug = '/expenses/index';

		$form->setAction(Zend_Registry::get('config')->moxie->settings->url.$slug)->setMethod('post');

		$st_categories = $this->categories->getCategoriesForView(Categories::EXPENSES);
		$st_categories[0] = '---';
		asort($st_categories);

		$multiOptions = new Zend_Form_Element_Select('category');
		$multiOptions->setName('category_search');
		$multiOptions->setLabel($st_lang['expenses_category']);
		$multiOptions->addMultiOptions($st_categories);
		$multiOptions->setValue($request->getParam('category_search', ''));
		$form->addElement($multiOptions);

		// @todo autocomplete for tags
		$form->addElement('text', 'tag', array('label' => $st_lang['search_tag'], 'value' => $request->getParam('tag', '')));

		$form->addElement('text', 'note', array('label' => $st_lang['search_note'], 'value' => $request->getParam('note', '')));

		$form->addElement('text', 'amount_min', array('label' => $st_lang['search_amount_min'], 'value' => $request->getParam('amount_min', 0)));
		$form->addElement('text', 'amount_max', array('label' => $st_lang['search_amount_max'], 'value' => $request->getParam('amount_max', '')));

		$form->addElement('text', 'date_min', array('label' => $st_lang['search_date_min'], 'value' => $request->getParam('date_min', date('Y-m-01'))));
		$form->addElement('text', 'date_max', array('label' => $st_lang['search_date_max'], 'value' => $request->getParam('date_max', date('Y-m-15'))));
		$form->addElement('submit','submit', array('label' => $st_lang['search_send']));
		return $form;
	}
	
	/**
	 * This function generates the form to add expenses.
     * @param array $st_expense
     * @return Zend_Form
	 */
	private function getForm($st_expense) {
		global $st_lang;
		$form  = new Zend_Form();

		if(empty($st_expense['id'])) {
			$in_sum_type = "hidden";
			$in_sum_value = 1;
			$slug = '/expenses/add';
		}
		else {
			$in_sum_type = "checkbox";
			$in_sum_value = $st_expense['in_sum'];
			$slug = '/expenses/update';
		}

        $form->setAction(Zend_Registry::get('config')->moxie->settings->url.$slug)
            ->setMethod('post');
		     
		$form->setAttrib('id', 'login');

		$st_categories = $this->categories->getCategoriesForView(Categories::EXPENSES);
		asort($st_categories);
        if(empty($st_expense['category'])) {
            reset($st_categories);
            $st_expense['category'] = key($st_categories);
            $this->currentCategory = $st_expense['category'];
        }

		$form->addElement('text', 'amount', array('label' => $st_lang['expenses_amount'], 'value' => $st_expense['amount']));
        $multiOptions = new Zend_Form_Element_Select('category');
        $multiOptions->setName('category');
        $multiOptions->setLabel($st_lang['expenses_category']);
        $multiOptions->addMultiOptions($st_categories);
        $multiOptions->setValue(array($st_expense['category']));
        $form->addElement($multiOptions);

		$form->addElement($in_sum_type, 'in_sum', array('value' => $in_sum_value));

		$form->addElement('text', 'note', array('label' => $st_lang['expenses_note'], 'value' => $st_expense['note']));
		$form->addElement('text', 'date', array('label' => $st_lang['expenses_date'], 'value' => $st_expense['date']));
		$form->addElement('submit','submit', array('label' => $st_lang['expenses_send']));

        $form->addElement('hidden', 'checked', array('value' => $st_expense['in_sum']));
        $form->addElement('hidden', 'user_owner', array('value' => $st_expense['user_owner']));
        $form->addElement('hidden', 'id', array('value' => $st_expense['id']));
		return $form;
	}
	
	/**
	 * Returns the monthly expense for a year.
	 * @return array
	 */
	private function getMonthExpensesData() {
		$st_data = array();
		$i_dateLimit = date("Y-m-01 00:00:00", strtotime("-12 months"));
		
		$o_rows = $this->expenses->getMonthExpensesData($_SESSION['user_id'], $i_dateLimit);

		foreach ($o_rows as $key => $value) {
			try {
				$timestamp = mktime(0, 0, 0, $value['month'], 1, $value['year']);
				$st_data[] = array(
						date("M", $timestamp),
						(float)$value['amount']
				);
			}
			catch(Exception $e) {
				error_log(__METHOD__.": ".$e->getMessage());
				error_log(__METHOD__." data: ".$key." ".print_r($value,true));
			}
		}
		$st_data = array_merge(array(array('Month', 'Expense')), $st_data);
		return $st_data;
	}

	/**
	 * Shows the expenses view.
	 */
	public function indexAction() {
		global $st_lang;

		// list current month by default
		// allow navigate through months and years
		$i_month = $this->getRequest()->getParam('month', date('n'));
		$i_year = $this->getRequest()->getParam('year', date('Y'));
		$this->currentCategory = $this->getRequest()->getParam('category', 0);
		$s_tag = urldecode($this->getRequest()->getParam('tag', null));
		$s_toExcel  = $this->getRequest()->getParam('to_excel');

		try {
			$st_data = $this->expenses->getExpenses($_SESSION['user_id'], $i_month, $i_year, $this->getRequest()->getParams());
			if((empty($this->currentCategory) && empty($s_tag)) || !empty($this->currentCategory)) {
				$st_list = $this->expenses->get($_SESSION['user_id'],Categories::EXPENSES, $i_month, $i_year, $this->currentCategory, $this->getRequest()->getParams());
			}
	        else {
		        $st_list = $this->expenses->getTaggedExpenses($_SESSION['user_id'], $i_month, $i_year, $s_tag);
	        }

			$i_tag = !empty($s_tag) ? $this->tags->findIdTagByName($_SESSION['user_id'], $s_tag) : null;
        }
        catch(Exception $e) {
	        error_log($e->getMessage());
            $st_data = array();
            $st_list = array();
        }

		if($s_toExcel == true) {
			$this->exportToExcel($st_list);
		}

        $st_expenses = array(
            'id' => null,
            'amount' => '0.00',
            'category' => 0,
            'note' => '',
            'date' => date('Y-m-d'),
            'in_sum' => 0,
            'user_owner' => $_SESSION['user_id']
        );
        $form = $this->getForm($st_expenses);

		$this->view->assign('expenses', $st_data);
		$this->view->assign('expenses_label', $st_lang['expenses_monthly']);
		$this->view->assign('month_expenses', json_encode($this->getMonthExpensesData()));
		$this->view->assign('month_expenses_label', $st_lang['expenses_by_months']);
		$this->view->assign('budget', $this->budgets->getBudget($_SESSION['user_id']));
		$this->view->assign('list', $st_list);
		$this->view->assign('year', $i_year);
		$this->view->assign('month', $i_month);
		$this->view->assign('category', $this->currentCategory);
		$this->view->assign('tag', $i_tag);
		$this->view->assign('form', $form);
		$this->view->assign('tag_list', $this->tags->getTagsByUser($_SESSION['user_id']));
        $this->view->assign('used_tag_list', $this->tags->getUsedTagsByUser($_SESSION['user_id']));
		$this->view->assign('show_categories_filter', $this->showCategoriesFilter);
		$this->view->assign('show_tags_filter', $this->showTagsFilter);
		$this->view->assign('search_form', $this->getSearchForm($this->getRequest()));
	}

	/**
	 * Exports to excel the data currently shown in the view.
	 * @param array|array[] $st_data containing rows with columns date, amount, name and note.
	 */
	private function exportToExcel($st_data) {
		$this->getResponse()
				->setHttpResponseCode(200)
				->setHeader('Content-Type', 'text/csv; charset=utf-8')
				->setHeader('Content-Disposition', 'attachment; filename='.date('Y-m-d').'.csv')
				->setHeader('Pragma', 'no-cache')
				->sendHeaders();

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
            $searchTag = str_replace("'", "\\'", $tag);
			$tagId = array_search($searchTag, $existingTags);
			if($tagId === FALSE) {
				$tagId = $this->tags->addTag($_SESSION['user_id'], $tag);
			}
			$this->transactionTags->addTagToTransaction($expenseId, $tagId);
		}
	}
	
	/**
	 * Adds an expense and shows expenses index again
	 * @author	hmeza
	 */
	public function addAction() {
		$st_form = $this->getRequest()->getPost();
		$st_form['amount'] = str_replace(",",".",$st_form['amount']);
		$st_form['note'] = $this->getRequest()->getParam('note', "");
		$st_form['category'] = $this->getRequest()->getParam('category', null);
		$st_form['date'] = str_replace('/', '-', $st_form['date']);
		if(empty($st_form['category'])) {
			throw new Exception("Empty category not allowed for expenses");
		}
		if(empty($_SESSION['user_id'])) {
			$this->redirect('/index');
		}
		try {
			$expenseId = $this->expenses->addExpense($_SESSION['user_id'], $st_form['date'], $st_form['amount'], $st_form['category'], $st_form['note']);
			if (!empty($_POST['taggles'])) {
				$this->updateTags($_POST['taggles'], $expenseId);
            }
		}
		catch(Zend_Db_Statement_Exception $e) {
			throw new Exception("Database error in ".__METHOD__);
		}
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * Edits a given expense
	 * @author	hmeza
	 */
	public function editAction() {
		global $st_lang;
		
		$i_expensePK = $this->getRequest()->getParam('id');
		$i_month = $this->getRequest()->getParam('month', date('n'));
		$i_year = $this->getRequest()->getParam('year', date('Y'));
		$this->currentCategory = $this->getRequest()->getParam('category', 0);
		$s_tag = $this->getRequest()->getParam('tag', null);

        // retrieve data to fill the form
        $st_expense = $this->expenses->getExpenseByPK($i_expensePK);
        if($st_expense['user_owner'] != $_SESSION['user_id']) {
            throw new Exception("Access error");
        }
		$st_data = $this->expenses->getExpenses($_SESSION['user_id'], $i_month, $i_year);
        $form = $this->getForm($st_expense);

		if(empty($i_category)) {
			$i_category = $this->currentCategory;
		}

		try {
			$i_tag = !empty($s_tag) ? $this->tags->findIdTagByName($_SESSION['user_id'], $s_tag) : null;
		}
		catch(Exception $e) {
			error_log("error recovering tags");
			$i_tag = 0;
		}
		
		$this->view->assign('expenses', $st_data);
		$this->view->assign('expenses_label', $st_lang['expenses_monthly']);
		$this->view->assign('month_expenses', json_encode($this->getMonthExpensesData()));
		$this->view->assign('month_expenses_label', $st_lang['expenses_by_months']);
		$this->view->assign('budget', $this->budgets->getBudget($_SESSION['user_id']));
		$this->view->assign('list', $this->expenses->get($_SESSION['user_id'],Categories::EXPENSES,$i_month,$i_year));
		$this->view->assign('year', $i_year);
		$this->view->assign('month', $i_month);
		$this->view->assign('category', $i_category);
		$this->view->assign('tag', $i_tag);
		$this->view->assign('form', $form);
		$this->view->assign('tags', $this->transactionTags->getTagsForTransaction($i_expensePK));
		$this->view->assign('tag_list', $this->tags->getTagsByUser($_SESSION['user_id']));
		$this->view->assign('used_tag_list', $this->tags->getUsedTagsByUser($_SESSION['user_id']));
		$this->view->assign('show_categories_filter', $this->showCategoriesFilter);
		$this->view->assign('show_tags_filter', $this->showTagsFilter);
		$this->view->assign('search_form', $this->getSearchForm($this->getRequest()));
		$this->render('index');
	}
	
	/**
	 * Updates an expense
	 * @author	hmeza
	 */
	public function updateAction() {
		$st_params = $this->getRequest()->getPost();
		$i_expensePK = $st_params['id'];

        // retrieve data to perform user check
        $st_expense = $this->expenses->getExpenseByPK($i_expensePK);
        if($st_expense['user_owner'] != $_SESSION['user_id']) {
            throw new Exception("Access error");
        }

		$originalExpenseDate = explode("-", $st_expense['date']);
		$this->transactionTags->removeTagsFromTransaction($i_expensePK);
		if(!empty($_POST['taggles'])) {
			$this->updateTags($_POST['taggles'], $i_expensePK);
		}

		$this->expenses->updateExpense($i_expensePK, $st_params);
		$this->getResponse()->setRedirect('/expenses/index/month/'.$originalExpenseDate[1].'/year/'.$originalExpenseDate[0]);
	}
	
	/**
	 * Deletes a given expense
	 * @author	hmeza
	 */
	public function deleteAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		try {
			$this->transactionTags->removeTagsFromTransaction($i_expensePK);
			$this->expenses->deleteByUser($_SESSION['user_id'], $i_expensePK);
		} catch (Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
		}
		$this->_helper->redirector('index','expenses');
	}

	/**
	 * Marks an expense to appear or not in sums
	 * @author	hmeza
	 */
	public function marklineAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		$this->expenses->updateExpense($i_expensePK);
		$this->_helper->redirector('index','expenses');
	}
}
