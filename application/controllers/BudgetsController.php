<?php
/** Zend_Controller_Action */
include 'application/models/Budgets.php';
include 'application/models/Categories.php';
class BudgetsController extends Zend_Controller_Action
{
	private $budgets;
	
	public function init() {
		$this->budgets = new Budgets();		
	}
	
	public function indexAction() {
		$o_categories = new Categories();
		$st_categories = $o_categories->getCategoriesByUser($_SESSION['user_id']);
		foreach($st_categories as $key => $value) {
			// get budget for this category
			$o_budget = $this->budgets->fetchRow(
							$this->budgets->select()
							->where('category = '.$value['id1'])
						);
			$st_categories[$key]['budget'] = (!empty($o_budget)) ? $o_budget->amount : 0;
		}
		$this->view->assign('categories',$st_categories);
		//$this->_helper->redirector('index','budgets');
	}
	
	/**
	 * @desc	Add a line on budget
	 */
	public function addAction() {
		$o_income = $this->getRequest()->getPost();
		$st_data = array(
			'user_owner'	=>	$_SESSION['user_id'],
			'category'		=>	$o_income['category'],
			'amount'		=>	$o_income['amount'],
			'date_created'	=>	date('Y-m-d H:i:s'),
		);
		try {
			$result = $this->budgets->insert($st_data);
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
			try {
				$cond = 'category = '.$st_data['category'];
				$result = $this->budgets->update($st_data,$cond);
			}
			catch(Exception $e) {
				error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
			}
			error_log($result.' row(s) updated');
		}
		$this->_helper->redirector('index','budgets');
	}
}
?>