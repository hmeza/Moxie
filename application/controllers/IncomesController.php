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
		include_once('Zend/Form.php');
		include_once('application/models/Categories.php');
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
	
	private function getEditForm($st_income) {
		include_once('Zend/Form.php');
		include_once('Zend/Form/Element/Select.php');
		include_once('application/models/Categories.php');
		$form  = new Zend_Form();
		$categories = new Categories();
		
		// get categories and prepare them for view
		$s_categories = $categories->getCategoriesByUser(Categories::INCOMES);
		foreach($s_categories as $key => $value) {
			$formCategories[$value['id1']] = $value['name2'];
			if (!empty($value['name1'])) {
				$formCategories[$value['id1']] = $value['name1'].' - '.$formCategories[$value['id1']];
			}
		}
		
		$form->setAction('/incomes/update')
		     ->setMethod('post');
		     
		$form->setAttrib('id', 'login');
		
		$form->addElement('hidden', 'user_owner', array('value' => $st_income[0]['user_owner']));
		$form->addElement('hidden', 'id', array('value' => $st_income[0]['id']));
		
		$form->addElement('text', 'amount', array('label' => 'Amount', 'value' => $st_income[0]['amount']));
		
		$multiOptions = new Zend_Form_Element_Select('category', $categories->getCategoriesForView(Categories::INCOMES));
		$multiOptions->setLabel('Category name');
		$multiOptions->addMultiOptions($categories->getCategoriesForView(Categories::INCOMES));
		$multiOptions->setValue(array($st_income[0]['category']));
		$form->addElement($multiOptions);
		
		$form->addElement('text', 'note', array('label' => 'Note', 'value' => $st_income[0]['note']));
		
		$s_date = explode(" ", $st_income[0]['date']);
		$form->addElement('text', 'date', array('label' => 'Date', 'value' => $s_date[0]));
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

		$this->view->assign('list', $this->incomes->getIncomes($_SESSION['user_id'],0,$i_year));
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
	
	/**
	 * @desc	Edit an income
	 * @author	hmeza
	 * @since	2011-06-13
	 */
	public function editAction() {
		$i_incomePK = $this->getRequest()->getParam('id');
		$st_income = $this->incomes->find($i_incomePK);
		
		$i_year = $this->getRequest()->getParam('year');
		$i_year = (isset($i_year)) ? $this->getRequest()->getParam('year') : date('Y');
		
		$this->view->assign('list', $this->incomes->getIncomes($_SESSION['user_id'],0,$i_year));
		$this->view->assign('year', $i_year);
		$this->view->assign('form', $this->getEditForm($st_income));
		$this->render('index');
	}
	
	/**
	 * @desc	Update income
	 * @author	hmeza
	 * @since	2011-06-13
	 */
	public function updateAction() {
		$st_params = $this->getRequest()->getPost();
		$i_incomePK = $st_params['id'];
		$i_userOwner = $st_params['user_owner'];
		unset($st_params['submit']);
		
		try {
			$this->incomes->update($st_params, 'id = '.$i_incomePK.' AND user_owner = '.$i_userOwner);
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
		$this->_helper->redirector('index','incomes');
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