<?php
/** Zend_Controller_Action */
include 'application/models/Categories.php';
class CategoriesController extends Zend_Controller_Action
{
	private $categories;
	
	public function init() {
		$this->categories = new Categories();		
	}
	
    public function indexAction()
    {
		$this->view->assign('list', $this->categories->getCategoriesByUser('1'));
    }
    
   
    public function addAction() {
    	try {
	    	$data = $this->_request->getParams();
	    	
	    	$this->categories->addCategory($data);
	    	
	    	$this->view->assign('list', $this->categories->getCategoriesByUser('1'));
	    	$this->render('index');
    	} catch (Exception $e) {
    		echo 'Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage();
    	}
    }
}
?>