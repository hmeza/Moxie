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
    	
    	$form->setAction('/categories/add')->setMethod('post');
    	$form->addElement('select', 'parent', array(
			'label' => 'Category parent',
			'multioptions' => $this->categories->getCategoriesForSelect(),
			)
		);
		$form->addElement('text', 'name', array('label' => 'Category name'));
		$form->addElement('text', 'description', array('label' => 'Category description'));
		$form->addElement('submit','submit', array('label' => 'Enviar'));
    	
		return $form;
	}
	
	private function getEditForm($i_categoryPK) {
		include('Zend/Form.php');
		$form  = new Zend_Form();
		
		// retrieve data to fill the form
		$st_category = $this->categories->find($i_categoryPK);

		$form->setAction('/categories/update')->setMethod('post');
		
		$form->addElement('hidden', 'id', array('value' => $i_categoryPK));
		// Add select
		$form->addElement('select', 'parent', array(
			'label' => 'Category parent',
			'multioptions' => $this->categories->getCategoriesForSelect(),
			'value'	=>	$st_category[0]['parent']
			)
		);
		$form->addElement('text', 'name', array('label' => 'Category name', 'value' => $st_category[0]['name']));
		$form->addElement('text', 'description', array('label' => 'Category description', 'value' => $st_category[0]['description']));
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
    	$this->_helper->redirector('index','categories');
    }
    
    public function editAction() {
    	$this->view->assign('form', $this->getEditForm($this->_request->getParam('id')));
    	$this->view->assign('list', $this->mountCategoryTree($this->categories->getCategoriesByUser($_SESSION['user_id'])));
    	$this->render('index');
    }
    
    public function updateAction() {
        try {
	    	$data = $this->_request->getParams();
	    	$st_update = array(
	    		'name'	=>	$data['name'],
	    		'description'	=>	$data['description'],
	    		'parent'		=>	$data['parent']
	    	);
	    	$this->categories->update($st_update,'id = '.$data['id'].' AND user_owner = '.$_SESSION['user_id']);
		} catch (Exception $e) {
    		error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
    	}
    	$this->_helper->redirector('index','categories');
    }
    
    public function deleteAction() {
    	// TODO: check if category has expenses or incomes
    	// if so, assign it before deleting
    	// delete category
		$i_id = $this->getRequest()->getParam('id');
		try {
			$this->categories->delete('id = '.$i_id);
		}
		catch (Expenses $e) {
			error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
		}
		$this->_helper->redirector('index','categories');
    }
}
?>