<?php
include_once( 'application/3rdparty/ofc/open-flash-chart.php' );

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
		$this->view->assign('budget_expenses', $this->categories->getCategoriesForView(1));
		$this->view->assign('budget_incomes', $this->categories->getCategoriesForView(2));
		$this->view->assign('expenses', $expenses);
		$this->view->assign('incomes', $incomes);
		$this->view->assign('budget', $this->budgets->getYearBudgets($_SESSION['user_id'], date('Y')));
		$this->view->assign('data', $data);
	}
	
	/**
	 * Generates year income graph.
	 * @author	hmeza
	 * @since	2011-06-01
	 */	
	public function incomestatsAction() {
		global $st_lang;
		$mydata = $this->getRequest()->getParam('mydata');
		// use the chart class to build the chart:
		$data = explode(":",$mydata);
		$user = (empty($data[1])) ? 0 : $data[1];
		
		$db = Zend_Registry::get('db');

		$s_select = $db->select()
			->from('incomes',array('YEAR(date)','MONTH(date)','sum(amount)'))
			->where('in_sum = 1')
			->where('user_owner = '.$user)
			->group('YEAR(date)')
			->order('YEAR(date)');
		$o_rows = $db->fetchAll($s_select);
	
		$data = array();
		$labels = array();
		
		$bar = new bar_outline(50, '#060606', '#040404');
		$bar->key($st_lang['incomes_by_years'], 10);
		$bar->data = array();
		$months = array();
		foreach($o_rows as $key => $value) {
			$bar->data[] = $value['sum(amount)'];
			$years[] = $value['YEAR(date)'];
		}
		
		$g = new graph();
		$g->title( $st_lang['incomes_stats'], '{font-size: 20px;}' );
		$g->data_sets[] = $bar;
		$g->bg_colour = '0xEFFFEF';	// soft green
		$g->bg_colour = '0xE3F0FD';
		$g->bg_colour = '0xFFFFFF';
		$g->set_x_labels( $years );
		//
		// set the X axis to show every 2nd label:
		//
		$g->set_x_label_style( 10, '#9933CC', 0, 1 );
		//
		// and tick every second value:
		//
		$g->set_x_axis_steps( 1 );
		//
		$g->set_inner_background( '#E3F0FD', '#ABD7E6', 90 );
		
		$g->set_y_max( 50000 );
		$g->y_label_steps( 4 );
		$g->set_y_legend($st_lang['incomes_yearly'], 12, '#736AFF' );
		echo $g->render();
		
		
		// display the data
		echo $g->render();
		$this->_helper->viewRenderer->setNoRender();
	}
}
?>
