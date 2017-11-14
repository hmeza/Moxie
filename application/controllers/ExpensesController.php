<?php
/** Zend_Controller_Action */
class ExpensesController extends TransactionsController
{
	/** @var Expenses */
	private $expenses;
	/** @var Budgets */
	private $budgets;
	/** @var Tags */
	private $tags;
	/** @var  Favourites */
	private $favourites;

	public function init() {
		parent::init();
		$this->expenses = new Expenses();
		$this->budgets = new Budgets();
		$this->tags = new Tags();
		$this->favourites = new Favourites();
	}

	/**
	 * Shows the expenses view.
	 * Receives call from export to excel too.
	 */
	public function indexAction() {
		$st_expense = array(
				'id' => null,
				'amount' => '0.00',
				'category' => 0,
				'note' => '',
				'date' => date('Y-m-d'),
				'in_sum' => 1,
				'user_owner' => $_SESSION['user_id'],
				'favourite' => 0
		);

		$st_params = $this->getParameters();

		$st_list = $this->expenses->get($_SESSION['user_id'],Categories::EXPENSES, $st_params);

		if (isset($st_params['o'])) {
			$st_params['o'] = ($st_params['o'][0] == '-')
					? substr($st_params['o'], 1, strlen($st_params['o'])-1)
					: "-".$st_params['o'];
		}

		if($this->getRequest()->getParam('to_excel') == true) {
			$this->exportToExcel($st_list);
		}

		$this->assignViewData($st_list, $st_params, $st_expense);
	}

	/**
	 * Adds an expense and shows expenses index again
	 * @author	hmeza
	 */
	public function addAction() {
		$st_form = $this->getRequest()->getPost();
		$st_form['amount'] = str_replace(",",".",$st_form['amount']);
		$st_form['date'] = str_replace('/', '-', $st_form['date']);
		if(empty($st_form['category'])) {
			throw new Exception("Empty category not allowed for expenses");
		}
		if(empty($_SESSION['user_id'])) {
			$this->redirect('/index');
		}
		try {
			$expenseId = $this->expenses->addExpense($_SESSION['user_id'], $st_form);
			if (!empty($_POST['tags'])) {
				$this->updateTags(explode(",", $_POST['tags']), $expenseId);
            }
		}
		catch(Zend_Db_Statement_Exception $e) {
            error_log(__METHOD__.": ".$e->getMessage());
			throw new Exception("Error adding expense");
		}
		$this->_helper->redirector('index','expenses');
	}
	
	/**
	 * Edits a given expense
	 * @author	hmeza
	 */
	public function editAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		// retrieve data to fill the form
		$st_expense = $this->expenses->getExpenseByPK($i_expensePK);
		if($st_expense['user_owner'] != $_SESSION['user_id']) {
			throw new Exception("Access error");
		}

		$st_params = $this->getParameters();

		$st_list = $this->expenses->get($_SESSION['user_id'],Categories::EXPENSES, $st_params);

		if (isset($st_params['o'])) {
			$st_params['o'] = "-".$st_params['o'];
		}

		$this->assignViewData($st_list, $st_params, $st_expense);
		$this->view->assign('tags', $this->transactionTags->getTagsForTransaction($i_expensePK));
		$this->render('index');
	}
	
	/**
	 * Updates an expense
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
		if(!empty($st_params['tags'])) {
			$this->updateTags(
					explode(",", $st_params['tags']),
					$i_expensePK
			);
		}

		$this->expenses->updateExpense($st_params);
		$this->getResponse()->setRedirect('/expenses/index/month/'.$originalExpenseDate[1].'/year/'.$originalExpenseDate[0]);
	}
	
	/**
	 * Deletes an expense.
	 */
	public function deleteAction() {
		$i_expensePK = $this->getRequest()->getParam('id');
		try {
			$this->transactionTags->getAdapter()->beginTransaction();
			$this->favourites->deleteByTransactionId($i_expensePK);
			$this->transactionTags->removeTagsFromTransaction($i_expensePK);
			$this->expenses->delete($i_expensePK, $_SESSION['user_id']);
			$this->transactionTags->getAdapter()->commit();
		} catch (Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
			$this->transactionTags->getAdapter()->rollBack();
		}
		$this->_helper->redirector('index','expenses');
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
			$in_sum_value = 1;
			$slug = '/expenses/add';
			$tag_value = '';
		}
		else {
			$in_sum_value = $st_expense['in_sum'];
			$slug = '/expenses/update';
			$tag_list = $this->transactionTags->getTagsForTransaction($st_expense['id']);
			$tag_value = implode(", ", $tag_list);
		}

		$form->setAction(Zend_Registry::get('config')->moxie->settings->url.$slug)
				->setMethod('post');

		$form->setAttrib('id', 'add_expense');

        $form->setDecorators(array(
            'FormElements',
            array('HtmlTag',array('tag' => 'table')),
            'Form'
        ));

		$st_categories = $this->categories->getCategoriesForView(Categories::EXPENSES);
		asort($st_categories);
		if(empty($st_expense['category'])) {
			reset($st_categories);
			$st_expense['category'] = key($st_categories);
		}

		$f_expense = !empty($st_expense['amount']) && $st_expense['amount'] != '0.00' ? $st_expense['amount'] : '';

        $form_elements = array();

        $favs = $this->expenses->getFavourites($_SESSION['user_id']);
        if(!empty($favs)) {
            $st_favs = array(0 => '');
            foreach ($favs as $fav) {
                $st_favs[$fav['id']] = $fav['note'];
            }
            $favouritesOptions = new Zend_Form_Element_Select('category');
            $favouritesOptions->setName('favourites');
            $favouritesOptions->setLabel($st_lang['use_favourite']);
            $favouritesOptions->addMultiOptions($st_favs);
            $favouritesOptions->setValue(array($st_expense['category']));
            $favouritesOptions->setAttrib('class', 'form-control font-weight-bold');
            $form_elements[] = $favouritesOptions;
        }
        
        $form_elements[] = new Zend_Form_Element_Text('amount' , array('label' => $st_lang['expenses_amount'], 'value' => $f_expense, 'placeholder' => '0,00', 'class' => 'form-control'));
        $form_elements[] = new Zend_Form_Element_Text('note' , array('label' => $st_lang['expenses_note'], 'value' => $st_expense['note'], 'class' => 'form-control'));
        $form_elements[] = new Zend_Form_Element_Date('date' , array('label' => $st_lang['expenses_date'], 'value' => $st_expense['date'], 'class' => 'form-control'));

        $multiOptions = new Zend_Form_Element_Select('category');
        $multiOptions->setName('category');
        $multiOptions->setLabel($st_lang['expenses_category']);
        $multiOptions->addMultiOptions($st_categories);
        $multiOptions->setValue(array($st_expense['category']));
        $multiOptions->setAttrib('class', 'form-control');
        $form_elements[] = $multiOptions;

        $form_elements[] = new Zend_Form_Element_Text('tags', array('id' => 'tags', 'label' => 'Tags', 'value' => $tag_value, 'placeholder' => $st_lang['tags_placeholder'], 'class' => 'form-control typeahead'));
        $form_elements[] = new Zend_Form_Element_Checkbox('in_sum', array('label' => $st_lang['in_sum_message'], 'value' => $in_sum_value, 'style' => 'width: 20px;', 'class' => 'checkbox-inline'));
        $form_elements[] = new Zend_Form_Element_Checkbox('favourite', array('label' => $st_lang['favourite_message'], 'value' => $st_expense['favourite'], 'style' => 'width: 20px;', 'class' => 'checkbox-inline'));

		if (isset($st_expense['id'])) {
			$remove = new Zend_Form_Element_Button('delete', array(
                'label' => $st_lang['expenses_delete'],
                'class' => 'btn btn-danger pull-right',
                'onclick' => 'confirmDelete("'.$st_expense['id'].'")'
            ));
            $form_elements[] = $remove;
		}

        $form_elements[] = new Zend_Form_Element_Submit('submit', array('label' => $st_lang['expenses_header'], 'class' => 'btn btn-primary pull-right'));
        if (isset($st_expense['id'])) {
            $form_elements[] = new Zend_Form_Element_Hidden('id', array('label' => null, 'value' => $st_expense['id']));
        }

		$this->prepareFormDecorators($form, $form_elements);

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
	 * Retrieves parameters from request.
	 */
	private function getParameters() {
		$st_params = $this->getRequest()->getParams();
		$st_params['month'] = $this->getRequest()->getParam('month', date('n'));
		$st_params['year'] = $this->getRequest()->getParam('year', date('Y'));
		$category = $this->getRequest()->getParam('category', null);
		if(empty($st_params['category_search'])) {
			$st_params['category_search'] = $category;
		}

		// convert month+year to filters for search
		if(empty($st_params['date_min']) && empty($st_params['date_max'])) {
			$current_date = $st_params['year'].'-'.$st_params['month'].'-01';
			$st_params['date_min'] = $current_date;
			$st_params['date_max'] = date("Y-m-t", strtotime($current_date));
		}

		return $st_params;
	}

	/**
	 * @param array $st_list
	 * @param array $st_params
	 * @param array $st_expense
	 * @throws Exception
	 */
	private function assignViewData($st_list, $st_params, $st_expense) {
		$this->view->assign('expenses', $this->expenses->getExpenses($_SESSION['user_id'], $st_params));
		$this->view->assign('month_expenses', json_encode($this->getMonthExpensesData()));
		$this->view->assign('budget', $this->budgets->getBudget($_SESSION['user_id']));
		$this->view->assign('list', $st_list);
		$this->view->assign('year', $st_params['year']);
		$this->view->assign('month', $st_params['month']);
		$this->view->assign('form', $this->getForm($st_expense));
		$this->view->assign('tag_list', $this->tags->getTagsByUser($_SESSION['user_id']));
		$this->view->assign('used_tag_list', $this->tags->getUsedTagsByUser($_SESSION['user_id']));
		$this->view->assign('search_form', $this->getSearchForm($this->getRequest()));
		$this->view->assign('view', 'expenses');
		if (isset($st_params['o'])) {
			$this->view->assign('o', $st_params['o']);
		}
		if($this->getRequest()->getParam('is_search', false) == 1) {
		    $this->view->assign('is_search', true);
        }
		$this->view->assign('favourites_json', json_encode($this->expenses->getFavourites($_SESSION['user_id'])));
	}
}
