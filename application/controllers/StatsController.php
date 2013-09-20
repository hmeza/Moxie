<?php
class StatsController extends Zend_Controller_Action {
	private $expenses;
	private $incomes;
	private $categories;
	private $budgets;
		
	public function init() {
		$this->expenses = new Expenses();
		$this->incomes = new Incomes();
		$this->categories = new Categories();
		$this->budgets = new Budgets();
	}
	
	/**
	 * Print detailed stats from user expenses and incomes.
	 * @todo	Use a group by to retrieve data and match with array
	 */
	public function indexAction() {
		$incomeStatsByCategory = $this->categories->getCategoriesForView(Categories::BOTH);
		$data = array();
		$db = Zend_Registry::get('db');
		foreach ($incomeStatsByCategory as $key => $value) {
			$data[$key]['index'] = $key;
			$data[$key]['name'] = $value;
			$s_select = $db->select()
				->from('expenses',
					array(
						new Zend_Db_Expr('SUM(amount)')
					)
				)
				->where("user_owner = ".$_SESSION['user_id'])
				->where("category = ".$key);
			$st_data = $db->fetchRow($s_select);
			$data[$key]['sumtotal'] = $st_data['SUM(amount)'];
			$s_select = $db->select()
				->from('expenses',
					array(
						new Zend_Db_Expr('SUM(amount)'),
						new Zend_Db_Expr('AVG(amount)')
					)
				)
				->where("user_owner = ".$_SESSION['user_id'])
				->where("category = ".$key)
				->where("YEAR(expense_date) = ".date('Y'));
			$st_data = $db->fetchRow($s_select);
			$data[$key]['sumyear'] = $st_data['SUM(amount)'];
			$data[$key]['avgyear'] = $st_data['AVG(amount)'];
		}
		// Get all categories, expenses and incomes from current year
		$expenses = array();
		$incomes = array();
		for($month = 1; $month <= 12; $month++) {
			$expenses[$month] = $this->expenses->getExpenses($_SESSION['user_id'], $month, date('Y'));
			$incomes[$month] = $this->incomes->getIncomes($_SESSION['user_id'],$month,date('Y'));
		}
		// get categories and order strings
		$st_expenses = $this->categories->getCategoriesForView(Categories::EXPENSES);
		$st_incomes = $this->categories->getCategoriesForView(Categories::INCOMES);
		asort($st_expenses);
		asort($st_incomes);
		
		$this->view->assign('budget_expenses', $st_expenses);
		$this->view->assign('budget_incomes', $st_incomes);
		$this->view->assign('expenses', $expenses);
		$this->view->assign('incomes', $incomes);
		$this->view->assign('budget', $this->budgets->getYearBudgets($_SESSION['user_id'], date('Y')));
		$this->view->assign('data', $data);
	}
}
?>
