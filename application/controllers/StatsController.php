<?php
class StatsController extends Zend_Controller_Action {
	/** @var Expenses */
	private $expenses;
    /** @var Incomes */
	private $incomes;
    /** @var Categories */
	private $categories;
    /** @var Budgets */
	private $budgets;
		
	public function init() {
		parent::init();
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
		foreach ($incomeStatsByCategory as $key => $value) {
			$data[$key]['index'] = $key;
			$data[$key]['name'] = $value;

            $st_data = $this->expenses->getSum($_SESSION['user_id'], $key);

			$data[$key]['sumtotal'] = $st_data['sum'];

            $st_data = $this->expenses->getStats($_SESSION['user_id'], $key);

			$data[$key]['sumyear'] = $st_data['sum'];
			$data[$key]['avgyear'] = $st_data['avg'];
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
		$st_perItemData = array();
		asort($st_expenses);
		asort($st_incomes);
		
		$this->view->assign('budget_expenses', $st_expenses);
		$this->view->assign('budget_incomes', $st_incomes);
		$this->view->assign('expenses', $expenses);
		$this->view->assign('incomes', $incomes);
		$this->view->assign('budget', $this->budgets->getYearBudgets($_SESSION['user_id'], date('Y')));
		$this->view->assign('data', $data);
		$this->view->assign('per_item_data', $st_perItemData);
	}
}