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
	    global $st_lang;

		$year = $this->getRequest()->getParam('year', date('Y'));
		$data = $this->getIncomeStatsByCategory();
		// Get all categories, expenses and incomes from current year
		$expenses = array();
		$incomes = array();
		$st_params = $this->getRequest()->getParams();
		for($month = 1; $month <= 12; $month++) {
			$current_date = $year.'-'.str_pad($month, 2, '0', STR_PAD_LEFT).'-01';
			$st_params['date_min'] = $current_date;
			$st_params['date_max'] = $st_params['date_max'] = date("Y-m-t", strtotime($current_date));
			$expenses[$month] = $this->expenses->get($_SESSION['user_id'], Categories::EXPENSES, $st_params)->toArray();
			$incomes[$month] = $this->incomes->get($_SESSION['user_id'],Categories::INCOMES, $st_params)->toArray();
		}
		// get expenses and incomes for all years
		$min_year = date('Y') - 4;
		$st_yearly = array();
		for($i = $min_year; $i <= date('Y'); $i++) {
			$st_yearly[$i] = $this->expenses->getYearly($_SESSION['user_id'], $i);
		}

		// get categories and order strings
		$st_expenses = $this->categories->getCategoriesForView(Categories::EXPENSES);
		$st_incomes = $this->categories->getCategoriesForView(Categories::INCOMES);
		asort($st_expenses);
		asort($st_incomes);

		$st_trends = $this->getTrends($st_expenses);

		// Check if there is a category on expenses that does not exist on categories, add "No category"
        list($st_expenses, $st_incomes, $expenses, $incomes) = $this->checkNoCategory($st_yearly, $st_expenses, $st_incomes, $st_lang, $expenses, $incomes);

		$this->view->assign('budget_expenses', $st_expenses);
		$this->view->assign('budget_incomes', $st_incomes);
		$this->view->assign('expenses', $expenses);
		$this->view->assign('incomes', $incomes);
		$this->view->assign('yearly', $st_yearly);
		$this->view->assign('data', $data);
		$this->view->assign('year', $year);
		$this->view->assign('trends', $st_trends);
	}

	private function getIncomeStatsByCategory() {
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
		return $data;
	}

	private function getTrends($st_expensesCategories) {
		$st_trends = array();
		foreach($st_expensesCategories as $key => $value) {
			$trend = $this->expenses->getMonthExpensesData($_SESSION['user_id'], date('Y-m-d', strtotime('-20 year')), $key);
			$st_trends[$key] = array(
					'name' => $value,
					'data' => $trend->toArray()
			);
		}
		return $st_trends;
	}

    /**
     * @param $st_yearly
     * @param $st_expenses
     * @param $st_incomes
     * @param $st_lang
     * @param $expenses
     * @param $incomes
     * @return array
     */
    private function checkNoCategory($st_yearly, $st_expenses, $st_incomes, $st_lang, $expenses, $incomes)
    {
        $empty_categories = array();
        foreach ($st_yearly as $st_year) {
            foreach ($st_year as $value) {
                $cat = $value['category'];
                if (!array_key_exists($cat, $st_expenses + $st_incomes)) {
                    $st_expenses[$cat] = $st_lang['empty_category'];
                    $st_incomes[$cat] = $st_lang['empty_category'];
                    $empty_categories[] = $cat;
                }
            }
        }
        if (!empty($empty_categories)) {
            foreach ($expenses as $month => $data) {
                foreach ($data as $key => $expense) {
                    if (empty($expense['category'])) {
                        $expenses[$month][$key]['category'] = $empty_categories[0];
                        $expenses[$month][$key]['name'] = $st_lang['empty_category'];
                        $expenses[$month][$key]['description'] = $st_lang['empty_category'];
                    }
                }
            }
            foreach ($incomes as $month => $data) {
                foreach ($data as $key => $income) {
                    if (empty($income['category'])) {
                        $incomes[$month][$key]['category'] = $empty_categories[0];
                        $incomes[$month][$key]['name'] = $st_lang['empty_category'];
                        $incomes[$month][$key]['description'] = $st_lang['empty_category'];
                    }
                }
            }
        }
        return array($st_expenses, $st_incomes, $expenses, $incomes);
    }
}