<?php
class CategoriesController extends Zend_Controller_Action
{
	/** @var Categories */
	private $categories;
	
	public function init() {
		parent::init();
		$this->categories = new Categories();
	}
	
	private function getForm() {
		global $st_lang;
    	$form  = new Zend_Form();
    	
    	$form->setAction('/categories/add')->setMethod('post');
    	$form->addElement('select', 'parent', array(
			'label' => $st_lang['category_parent'],
			'multioptions' => $this->categories->getCategoriesForSelect(3),
			)
		);
		$form->addElement('text', 'name', array('label' => $st_lang['category_name']));
		$form->addElement('text', 'description', array('label' => $st_lang['category_description']));
		
		$categoryTypes = array(Categories::EXPENSES => $st_lang['category_expense'], Categories::INCOMES => $st_lang['category_income'], Categories::BOTH => $st_lang['category_both']);
		$types = new Zend_Form_Element_Radio('type');
		$types->setRequired(true)  // field required
		->setLabel($st_lang['category_type'])
		->setValue(Categories::BOTH) // first radio button selected
		->setMultiOptions($categoryTypes);  // add array of values / labels for radio group
		$form->addElement($types);
		
		$form->addElement('submit','submit', array('label' => $st_lang['category_send']));
    	
		return $form;
	}
	
	private function getEditForm($i_categoryPK) {
		global $st_lang;
		$form  = new Zend_Form();
		
		// retrieve data to fill the form
		$st_category = $this->categories->find($i_categoryPK);

		$form->setAction('/categories/update')->setMethod('post');
		
		$form->addElement('hidden', 'id', array('value' => $i_categoryPK));
		// Add select
		$form->addElement('select', 'parent', array(
			'label' => $st_lang['category_parent'],
			'multioptions' => $this->categories->getCategoriesForSelect(3),
			'value'	=>	$st_category[0]['parent']
			)
		);
		$form->addElement('text', 'name', array('label' => $st_lang['category_name'], 'value' => $st_category[0]['name']));
		$form->addElement('text', 'description', array('label' => $st_lang['category_description'], 'value' => $st_category[0]['description']));
		
		$categoryTypes = array(Categories::EXPENSES => $st_lang['category_expense'], Categories::INCOMES => $st_lang['category_income'], Categories::BOTH => $st_lang['category_both']);
		$types = new Zend_Form_Element_Radio('type');
		$types->setRequired(true)  // field required
		->setValue($st_category[0]['type']) // first radio button selected
		->setMultiOptions($categoryTypes);  // add array of values / labels for radio group
		$form->addElement($types);
		
		$form->addElement('submit','submit', array('label' => $st_lang['category_send']));
		$form->addElement('button', 'delete',
				array(
					'label' => $st_lang['categories_delete'],
					'onclick' => 'window.location.replace("/categories/delete/id/'.$i_categoryPK.'");',
                    'class' => 'moxie'
				)
		);
		return $form;
	}
	
    public function indexAction() {
	    $this->view->assign('categories_form', $this->getForm());
	    $this->view->assign('categories_list', $this->categories->mountCategoryTree($this->categories->getCategoriesByUser(3), $_SESSION['user_id']));
	    $this->view->assign('categories_display', '');
		$this->_forward('index', 'users');
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
    	$this->view->assign('categories_form', $this->getEditForm($this->_request->getParam('id')));
    	$this->view->assign('categories_list', $this->categories->mountCategoryTree($this->categories->getCategoriesByUser(3), $_SESSION['user_id']));
	    $this->view->assign('categories_display', '');
    	$this->_forward('index', 'users');
    }
    
    public function updateAction() {
        try {
	    	$data = $this->_request->getParams();
	    	$st_update = array(
	    		'name'	=>	$data['name'],
	    		'description'	=>	$data['description'],
	    		'parent'		=>	$data['parent'],
	    		'type'			=>	$data['type']
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
			// delete children categories
			$this->categories->delete('parent = '.$i_id);
			$this->categories->delete('id = '.$i_id);
		}
		catch (Exception $e) {
			error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
		}
		$this->_helper->redirector('index','categories');
    }
}