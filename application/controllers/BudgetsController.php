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
	
	private function prepareCategoriesTree($st_categories) {
		$st_preparedTree = array();
		foreach($st_categories as $key => $value) {
			if (empty($value['id3'])) {
				$i_key = null;
				$st_value = null;
			}
			if (!empty($value['id3']) && $i_key == null) {
				$i_key = $value['id2'];
				$st_parentLine = array(
					'id1'	=>	$value['id1'],
					'name1'	=>	$value['name1'],
					'id2'	=>	$value['id2'],
					'name2'	=>	$value['name2'],
					'id3'	=>	null,
					'name3'	=>	null			
				);
				$st_preparedTree[] = $st_parentLine;
			}
			$st_preparedTree[] = $value;
		}
		return $st_preparedTree;
	}
	
	public function indexAction() {
		$o_categories = new Categories();
		$st_categories = $this->prepareCategoriesTree($o_categories->getCategoriesTree());
		foreach($st_categories as $key => $value) {
			// get budget for this category
			$i_categoryPK = (isset($value['id3'])) ? $value['id3'] : $value['id2'];
			$o_budget = $this->budgets->fetchRow(
							$this->budgets->select()
							->where('category = '.$i_categoryPK)
						);
			$st_categories[$key]['budget'] = (!empty($o_budget)) ? $o_budget->amount : 0;
		}
		$this->view->assign('categories',$st_categories);
	}
	
	public function addAction() {
		header("Cache-Control: no-cache");
		$st_data = array(
			'user_owner'	=>	$_SESSION['user_id'],
			'category'		=>	$this->getRequest()->getParam('category'),
			'amount'		=>	$this->getRequest()->getParam('amount'),
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
	}
}
?>