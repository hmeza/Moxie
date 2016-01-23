<?php
class BudgetsController extends Zend_Controller_Action
{
	/** @var Budgets */
	private $budgets;
	
	/**
	 * Initialize Budgets controller class.
	 */
	public function init() {
		$this->budgets = new Budgets();		
	}
	
	/**
	 * Mount the category tree for the current budget.
	 * @param array $st_categories
	 * @return array
	 */
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
	
	/**
	 * Retrieves the current budget and shows it.
	 */
	public function indexAction() {
		$o_categories = new Categories();
		$st_categories = $this->prepareCategoriesTree($o_categories->getCategoriesTree());
		foreach($st_categories as $key => $value) {
			// get budget for this category
			$i_categoryPK = (isset($value['id3'])) ? $value['id3'] : $value['id2'];
			$o_budget = $this->budgets->fetchRow(
							$this->budgets->select()
							->where('category = '.$i_categoryPK)
							->where('date_ended IS NULL')
						);
			$st_categories[$key]['budget'] = (!empty($o_budget)) ? $o_budget->amount : 0;
		}
		$this->view->assign('categories',$st_categories);
		$this->renderScript('categories/index.phtml'); 
	}
	
	/**
	 * Adds an amount to a category for the current budget.
	 * @todo	Handle exception with proper message
	 */
	public function addAction() {
		$b_found = false;
		header("Cache-Control: no-cache");
		$st_budget = $this->budgets->getBudget($_SESSION['user_id']);
		foreach ($st_budget as $key => $value) {
			if ($key == $this->getRequest()->getParam('category')) $b_found = true;
		}
		if ($b_found) {
			$cond = 'category = '.$this->getRequest()->getParam('category').' AND date_ended IS NULL';
			$st_data = array('amount'=>$this->getRequest()->getParam('amount'));
			try {
				$result = $this->budgets->update($st_data,$cond);
			}
			catch(Exception $e) {
				error_log(__METHOD__.": ".$e->getMessage());
			}
		}
		else {
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
				error_log(__METHOD__.": ".$e->getMessage());
			}
		}
	}
	
	/**
	 * Makes a snapshot of current budget and generates a new one.
	 * @todo	Handle exception with proper message
	 * @author	hmeza
	 * @since	2011-11-12
	 */
	public function snapshotAction() {
		header("Cache-Control: no-cache");
		try {
			error_log('snapshoting');
			$this->budgets->snapshot($_SESSION['user_id']);
			error_log('snapshoted');
		}
		catch (Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
		}
		$this->render('index','categories');
		return true;
	}
}
?>