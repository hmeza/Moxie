<?php
/** Zend_Controller_Action */
include 'application/models/Categories.php';
class CategoriesController extends Zend_Controller_Action
{
	private $categories;
	
	public function init() {
		$this->categories = new Categories();		
	}
	
	private function getForm() {
    	include('Zend/Form.php');
    	$form  = new Zend_Form();
    	$form->addElement('select', 'parent', array(
			'label' => 'Category parent',
			'multioptions' => $this->categories->getCategoriesForView()
			)
		);
		$form->addElement('text', 'name', array('label' => 'Category name'));
		$form->addElement('text', 'description', array('label' => 'Category description'));
		$form->addElement('submit','submit', array('label' => 'Enviar'));
    	
		return $form;
	}
	
	private function mountCategoryTree($st_categories) {
		$st_orderedCategories = array();
		return $st_categories;
	}
		
    public function indexAction() {
		$this->view->assign('form', $this->getForm());
		$this->view->assign('list', $this->mountCategoryTree($this->categories->getCategoriesByUser($_SESSION['user_id'])));
    }
    
   
    public function addAction() {
    	try {
	    	$data = $this->_request->getParams();
	    	$this->categories->addCategory($data);
		} catch (Exception $e) {
    		error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
    	}	    	
    	$this->view->assign('form', $this->getForm());
    	$this->view->assign('list', $this->mountCategoryTree($this->categories->getCategoriesByUser($_SESSION['user_id'])));
    	$this->render('index');
    }
    
    public function deleteAction() {
    	// check if category has expenses or incomes
    	// if so, assign it before deleting
    	// delete category
		$i_id = $this->getRequest()->getParam('id');
		try {
			$this->categories->delete('id = '.$i_id);
		}
		catch (Expenses $e) {
			error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
		}
		$this->view->assign('form', $this->getForm());
    	$this->view->assign('list', $this->mountCategoryTree($this->categories->getCategoriesByUser($_SESSION['user_id'])));
    	$this->render('index');
    }
}
?>