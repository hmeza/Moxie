<?php
/** Zend_Controller_Action */
class IncomesController extends TransactionsController
{
    /** @var Incomes */
	private $incomes;

	public function init() {
		parent::init();
		$this->incomes = new Incomes();
	}

	/**
	 * This function generates the form to add incomes.
	 * @param array $st_income
	 * @return Zend_Form
	 */
	private function getForm($st_income = array()) {
		global $st_lang;
		$form  = new Zend_Form();
		$categories = new Categories();

		$action = '/incomes/';
		$action .= isset($st_income[0]['id']) ? 'update' : 'add';

		// fix for datetime to date
		$s_date = explode(" ", $st_income[0]['date']);
		$st_income[0]['date'] = $s_date[0];

		$form->setAction($action)->setMethod('post');
		$form->setAttrib('id', 'login');
        $form->setDecorators(array(
            'FormElements',
            array('HtmlTag',array('tag' => 'table')),
            'Form'
        ));

        $form_elements = array();

        $form_elements[] = new Zend_Form_Element_Text('amount', array('label' => $st_lang['expenses_amount'], 'placeholder' => '0.00', 'value' => $st_income[0]['amount'], 'class' => 'form-control'));
        $form_elements[] = new Zend_Form_Element_Text('note', array('label' => $st_lang['expenses_note'], 'value' => $st_income[0]['note'], 'class' => 'form-control'));
        $form_elements[] = new Zend_Form_Element_Date('date', array('label' => $st_lang['incomes_date'], 'value' => $st_income[0]['date'], 'class' => 'form-control'));

		$multiOptions = new Zend_Form_Element_Select('category');
		$multiOptions->setLabel($st_lang['expenses_category']);
		$multiOptions->addMultiOptions($categories->getCategoriesForView(Categories::INCOMES));
		$multiOptions->setValue(array($st_income[0]['category']));
		$multiOptions->setAttrib('class', 'form-control');
		$form_elements[] = $multiOptions;
		
		if (isset($st_income[0]['id'])) {
            $id = $st_income[0]['id'];
            $remove = new Zend_Form_Element_Button('delete', array(
                'label' => 'Borrar',
                'class' => 'btn btn-danger pull-right',
                'onclick' => 'confirmDelete("'.$id.'")'
            ));
            $form_elements[] = $remove;
		}
		$submit = new Zend_Form_Element_Submit('submit', array('label' => $st_lang['income_header'], 'class' => 'btn btn-primary pull-right'));
		$form_elements[] = $submit;
        if (isset($st_income[0]['id'])) {
            $form_elements[] = new Zend_Form_Element_Hidden('id', array('value' => $id));
        }

        $this->prepareFormDecorators($form, $form_elements);
        if(isset($remove)) {
            $remove->removeDecorator("label");
        }
        $submit->removeDecorator("label");

		return $form;
	}

	/**
	 * Retrieve the yearly incomes.
	 * @return array
	 */
	public function getYearlyIncome() {
		global $st_lang;
		
        $o_rows = $this->incomes->getYearlyIncome($_SESSION['user_id']);
	
		$st_data = array(array($st_lang['incomes_date'], $st_lang['expenses_amount']));
		foreach($o_rows as $key => $value) {
			$st_data[] = array(
					(string)$value['date'],
					(float)$value['amount']
			);
		}
		return $st_data;
	}

	private function getViewData($st_data) {
		global $st_lang;

		$i_year = $this->getRequest()->getParam('year', date('Y'));
		$st_params = $this->getRequest()->getParams();
		// convert month+year to filters for search
		if(empty($st_params['date_min']) && empty($st_params['date_max'])) {
			$st_params['date_min'] = $i_year.'-01-01';
			$st_params['date_max'] = date("Y-12-t", strtotime($i_year.'-01-01'));
		}
		$st_params['category_search'] = $this->getRequest()->getParam('category_search', null);

		$this->view->assign('list', $this->incomes->get($_SESSION['user_id'],Categories::INCOMES, $st_params));
		$this->view->assign('graphData', json_encode($this->getYearlyIncome()));
		$this->view->assign('graphDataLabel', $st_lang['incomes_yearly']);
		$this->view->assign('graphDataLabelYear', $st_lang['incomes_by_years']);
		$this->view->assign('year', $i_year);
		$this->view->assign('form', $this->getForm($st_data));
		$this->view->assign('search_form', $this->getSearchForm($this->getRequest(), Categories::INCOMES));
	}
	
	/**
	 * Shows the expenses view
	 */
	public function indexAction() {
		$st_data = array(array(
			'id' => null,
			'amount' => null,
			'category' => null,
			'note' => '',
			'date' => date('Y-m-d H:i:s')
		));

		$this->getViewData($st_data);
	}
	
	/**
	 * Adds an expense and shows expenses index again
	 */
	public function addAction() {
		$st_income = $this->getRequest()->getPost();
		if(empty($st_income['category'])) {
			throw new Exception('Empty category not allowed for incomes');
		}
		if(empty($_SESSION['user_id'])) {
			$this->redirect('/index');
		}
		unset($st_income['submit']);
		$st_income['user_owner'] = $_SESSION['user_id'];
		$st_income['in_sum'] = 1;
		$st_income['amount'] = str_replace(",", ".", $st_income['amount']);
		$this->incomes->insert($st_income);
		$this->_helper->redirector('index','incomes');
	}
	
	/**
	 * Edit an income
	 */
	public function editAction() {
		$i_incomePK = $this->getRequest()->getParam('id');
		$st_income = $this->incomes->find($i_incomePK)->toArray();
        if(!isset($st_income[0]) || $st_income[0]['user_owner'] != $_SESSION['user_id']) {
            throw new Exception("Access error");
        }

		$this->getViewData($st_income);
		$this->render('index');
	}
	
	/**
	 * Update income
	 */
	public function updateAction() {
		$st_params = $this->getRequest()->getPost();
		$i_incomePK = $st_params['id'];
		$i_userOwner = $_SESSION['user_id'];
		unset($st_params['submit']);
        $st_params['amount'] = str_replace(",", ".", $st_params['amount']);
		
		try {
			$this->incomes->update($st_params, 'id = '.$i_incomePK.' AND user_owner = '.$i_userOwner);
		}
		catch (Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
		}
		$this->_helper->redirector('index','incomes');
	}
	
	/**
	 * Deletes a given income
	 */
	public function deleteAction() {
		try {
			$this->transactionTags->getAdapter()->beginTransaction();
			$this->transactionTags->removeTagsFromTransaction($this->getRequest()->getParam('id'));
			$this->incomes->delete($this->getRequest()->getParam('id'), $_SESSION['user_id']);
			$this->transactionTags->getAdapter()->commit();
		}
		catch (Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
			$this->transactionTags->getAdapter()->rollBack();
		}
		$this->_helper->redirector('index','incomes');
	}
}
