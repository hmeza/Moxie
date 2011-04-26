<?php
include_once( 'application/3rdparty/ofc/open-flash-chart.php' );
include_once( 'application/models/Expenses.php');
include_once( 'application/models/Incomes.php');
include_once( 'application/models/Categories.php');
include_once '../Zend/Zend/Db/Expr.php';

class StatsController extends Zend_Controller_Action {
	private $expenses;
	private $incomes;
	private $categories;
	
	public function init() {
		$this->expenses = new Expenses();
		$this->incomes = new Incomes();
		$this->categories = new Categories();
	}
	
	public function incomeStats() {
	
	}
	
	/**
	 * @desc	Print detailed stats from user expenses and incomes.
	 * @todo	Use a group by to retrieve data and match with array
	 */
	public function indexAction() {
		$incomeStatsByCategory = $this->categories->getCategoriesForView();
		$data = array();
		$db = Zend_Registry::get('db');
		foreach ($incomeStatsByCategory as $key => $value) {
			$data[$key]['index'] = $key;
			$data[$key]['name'] = $value;
			$s_select = $db->select()
				->from('expenses',
					array(
						new Zend_Db_Expr('SUM(amount)'),
						new Zend_Db_Expr('AVG(amount)')
					)
				)
				->where("user_owner = ".$_SESSION['user_id'])
				->where("category = ".$key);
			$st_data = $db->fetchRow($s_select);
			$data[$key]['sumtotal'] = $st_data['SUM(amount)'];
			$data[$key]['avgtotal'] = $st_data['AVG(amount)'];
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
		$this->view->assign('data', $data);
	}
	
	// TODO: generate graphs
	public function stats() {
		// use the chart class to build the chart:
		
		$data = explode(":",$_GET['mydata']);
		//$category = (empty($data[1])) ? 0 : $data[1];
		$scale = (empty($category)) ? 2500 : 500;
		$user = (empty($data[1])) ? 0 : $data[1];
		
		mysql_connect("127.0.0.1","root","0nr3fn1");
		mysql_select_db("moxie");
		$sql = 'select YEAR(expense_date), MONTH(expense_date),sum(amount)'
		.' from expenses'
		.' where in_sum = 1 AND user_owner = '.$user;
		if ($category != 0) $sql .= ' AND category = '.$category;
		$sql .= ' group by MONTH(expense_date),YEAR(expense_date)'
		.' order by YEAR(expense_date),MONTH(expense_date)';
		$rows = mysql_query($sql);
		
		$data = array();
		$labels = array();
		
		$bar = new bar_outline(50, '#060606', '#040404');
		$bar->key('By months', 10);
		$bar->data = array();
		$months = array();
		while ($value = mysql_fetch_array($rows)) {
			$bar->data[] = $value[2];
			$timestamp = mktime(0, 0, 0, $value[1], 1, 2005);
			$months[] = date("M", $timestamp);
		}
		mysql_free_result($rows);
		
		$g = new graph();
		$g->title( 'Monthly expense', '{font-size: 20px;}' );
		$timestamp = mktime(0, 0, 0, $month, 1, $year);
		$s_month = date("F", $timestamp);
		$g->data_sets[] = $bar;
		$g->bg_colour = '0xEFFFEF';	// soft green
		$g->bg_colour = '0xE3F0FD';
		$g->bg_colour = '0xFFFFFF';
		$g->set_x_labels( $months );
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
		
		$g->set_y_max( $scale );
		$g->y_label_steps( 4 );
		$g->set_y_legend( 'Monthly expenses', 12, '#736AFF' );
		echo $g->render();
		
		
		// display the data
		echo $g->render();
	}
	
	// TODO: generate graphs 2
	public function generate_graphs() {
		// Sacar suma de amounts ordenada por mes y año
		$sql = 'select YEAR(expense_date), MONTH(expense_date),sum(amount)'
		.' from expenses group by MONTH(expense_date),YEAR(expense_date)'
		.' order by YEAR(expense_date),MONTH(expense_date)';
		
		// Sacar suma de amounts ordenada por mes y año por categoria
		$sql = 'select YEAR(expense_date), MONTH(expense_date),sum(amount)'
		.' from expenses group by MONTH(expense_date),YEAR(expense_date)'
		.' where category = ?'
		.' order by YEAR(expense_date),MONTH(expense_date)';
	}
}
?>
