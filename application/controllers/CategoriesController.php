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
			'multioptions' => $this->categories->getCategoriesForSelect()
			)
		);
		$form->addElement('text', 'name', array('label' => 'Category name'));
		$form->addElement('text', 'description', array('label' => 'Category description'));
		$form->addElement('submit','submit', array('label' => 'Enviar'));
    	
		return $form;
	}
	
	private function mountCategoryTree($st_categories) {
		$st_parent = $this->categories->fetchRow($this->categories->select()
			->where('user_owner = '.$_SESSION['user_id'])
			->where('parent IS NULL'));
		$st_root = array(
			'id1'		=>	$st_parent->id,
			'parent1'	=>	null,
			'name1'		=>	null,
			'name2'		=>	'New category'
		);
		$st_parentCategories = array();
		$st_parentCategories[] = $st_root;
		foreach ($st_categories as $key => $value) {
			$st_parentCategories[] = $value;
		}
		error_log(print_r($st_parentCategories,true));
		return $st_parentCategories;
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