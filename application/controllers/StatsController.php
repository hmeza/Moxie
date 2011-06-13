<?php
include_once( 'application/3rdparty/ofc/open-flash-chart.php' );
include_once( 'application/models/Expenses.php');
include_once( 'application/models/Incomes.php');
include_once( 'application/models/Categories.php');
include_once '../Zend/Zend/Db/Expr.php';
include_once( 'application/3rdparty/ofc/open-flash-chart.php' );

class StatsController extends Zend_Controller_Action {
	private $expenses;
	private $incomes;
	private $categories;
		
	public function init() {
		$this->expenses = new Expenses();
		$this->incomes = new Incomes();
		$this->categories = new Categories();
	}
	
	/**
	 * @desc	Print detailed stats from user expenses and incomes.
	 * @todo	Use a group by to retrieve data and match with array
	 */
	public function indexAction() {
		$incomeStatsByCategory = $this->categories->getCategoriesForView(3);
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
		$this->view->assign('data', $data);
	}
	
	/**
	 * @desc	Generates year income graph.
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
	
	/**
	 * @desc	Generates expenses pie graph.
	 * @author	hmeza
	 * @since	2011-06-01
	 */
	public function dataAction() {
		$mydata = $this->getRequest()->getParam('mydata');
		$data = explode(":",$mydata);
		$month = (empty($data[1])) ? date('n') : $data[1];
		$year = (empty($data[3])) ? date('Y') : $data[3];
		$user = (empty($data[5])) ? 0 : $data[5]; 

		$g = new graph();
		$g->bg_colour = '0xFFFFFF';
		$g->pie(60,'#000000','{font-size: 12px; color: #000000;');

		$db = Zend_Registry::get('db');

		// get root category ID
		$s_select = $db->select()
			->from('categories', array('id'))
			->where('parent IS NULL')
			->where('user_owner = '.$user);
		$s_rows = $db->fetchAll($s_select);
		$i_id = $s_rows[0]['id'];

		$s_select = $db->select()
			->from(array('e'=>'expenses'), array('c.id','sum(e.amount)', 'c.name'))
			->join(array('c'=>'categories'), array())
			->where('c.id = e.category')
			->where('e.in_sum = 1')
			->where('e.user_owner = '.$user)
			->where('YEAR(e.expense_date) = '.$year)
			->where('MONTH(e.expense_date) = '.$month)
			->group('c.id')
			->order('c.id');
		$rows = $db->fetchAll($s_select);
		
		$data = array();
		$labels = array();
		foreach($rows as $key=>$value) {
			error_log(print_r($value,true));
			$data[] = $value['sum(e.amount)'];
			$labels[] = $value['name'];
		}
		
		$g->pie_values( $data, $labels);
		$g->pie_slice_colours( array('#d01f3c','#356aa0','#aaccaa','#adffaa','#aa5500','#060606','#CCFF66') );
		
		$g->set_tool_tip( '#val# €' );
		
		$timestamp = mktime(0, 0, 0, $month, 1, $year);
		$s_month = date("F", $timestamp);
		$g->title( $s_month." ".$year, '{font-size:18px; color: #000000}' );
		
		echo $g->render();
		$this->_helper->viewRenderer->setNoRender();
	}
	
	/**
	 * @desc	Generates monthly expenses graph.
	 * @author	hmeza
	 * @since	2011-06-01
	 */
	public function statsAction() {
		global $st_lang;
		$mydata = $this->getRequest()->getParam('mydata');
		// use the chart class to build the chart:
		
		$data = explode(":",$mydata);
		//$category = (empty($data[1])) ? 0 : $data[1];
		$scale = (empty($category)) ? 2500 : 500;
		$user = (empty($data[1])) ? 0 : $data[1];
		$i_dateLimit = date("Y-m-01 00:00:00", strtotime("-12 months"));
                
		$db = Zend_Registry::get('db');

		$s_category = ($category != 0) ? 'category = '.$category : '1=1';

		$s_query = $db->select()
			->from('expenses', array('YEAR(expense_date)','MONTH(expense_date)','sum(amount)'))
			->where('in_sum = 1')
			->where('user_owner = '.$user)
			->where('expense_date >= "'.$i_dateLimit.'"')
			->where($s_category)
			->group('MONTH(expense_date), YEAR(expense_date)')
			->order('YEAR(expense_date), MONTH(expense_date)');

		$o_rows = $db->fetchAll($s_query);
	
		$data = array();
		$labels = array();
		
		$bar = new bar_outline(50, '#060606', '#040404');
		$bar->key($st_lang['expenses_by_months'], 10);
		$bar->data = array();
		$months = array();
		foreach ($o_rows as $key => $value) {
			$bar->data[] = $value['sum(amount)'];
			$timestamp = mktime(0, 0, 0, $value['MONTH(expense_date)'], 1, 2005);
			$months[] = date("M", $timestamp);
		}
		
		$g = new graph();
		$g->title( $st_lang['expenses_monthly'] , '{font-size: 20px;}' );
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
		$g->set_y_legend( $st_lang['expenses_monthly'], 12, '#736AFF' );
		// display the data
		echo $g->render();
		$this->_helper->viewRenderer->setNoRender();
	}
}
?>
