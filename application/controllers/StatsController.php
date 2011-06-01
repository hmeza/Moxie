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
	 * @desc	Generates year income graph.
	 * @author	hmeza
	 * @since	2011-06-01
	 */	
	public function incomestatsAction() {
		$mydata = $this->getRequest()->getParam('mydata');
		// use the chart class to build the chart:
		$data = explode(":",$mydata);
		$user = (empty($data[1])) ? 0 : $data[1];
		
		mysql_connect("127.0.0.1","root","0nr3fn1");
		mysql_select_db("moxie");
		$sql = 'select YEAR(date), MONTH(date),sum(amount)'
		.' from incomes'
		.' where in_sum = 1 AND user_owner = '.$user
		.' group by YEAR(date)'
		.' order by YEAR(date)';
		$rows = mysql_query($sql);
		
		$data = array();
		$labels = array();
		
		$bar = new bar_outline(50, '#060606', '#040404');
		$bar->key('By years', 10);
		$bar->data = array();
		$months = array();
		while ($value = mysql_fetch_array($rows)) {
			$bar->data[] = $value[2];
			$years[] = $value[0];
		}
		mysql_free_result($rows);
		
		$g = new graph();
		$g->title( 'Income stats', '{font-size: 20px;}' );
		$timestamp = mktime(0, 0, 0, $month, 1, $year);
		$s_month = date("F", $timestamp);
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
		$g->set_y_legend( 'Yearly income', 12, '#736AFF' );
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
		//$data = explode(":",$_GET['mydata']);
		$month = (empty($data[1])) ? date('n') : $data[1];
		$year = (empty($data[3])) ? date('Y') : $data[3];
		$user = (empty($data[5])) ? 0 : $data[5]; 
		// use the chart class to build the chart:

		$g = new graph();
		
		$g->bg_colour = '0xFFFFFF';
		// Spoon sales, March 2007
		$g->pie(60,'#000000','{font-size: 12px; color: #000000;');
		//
		// pass in two arrays, one of data, the other data labels
		// get data from DB
		// get categories from DB
		mysql_connect("127.0.0.1","root","0nr3fn1");
		mysql_select_db("moxie");
		
		// get root category ID
		$sql = "select id from categories where parent IS NULL and user_owner = ".$user;
		$data = mysql_query($sql);
		$s_id = mysql_fetch_array($data);
		$i_id = $s_id['id'];
			
		$sql = "select c.id, sum(e.amount), c.name from expenses e, categories c where c.id = e.category and e.in_sum = 1 and e.user_owner = ".$user." and YEAR(e.expense_date) = ".$year." AND month(e.expense_date) = ".$month." group by c.id order by c.id;";
		
		$rows = mysql_query($sql);
		
		$data = array();
		$labels = array();
		while ($value = mysql_fetch_array($rows)) {
			$data[] = $value[1];
			$labels[] = $value[2];
		}
		mysql_free_result($rows);
		
		$g->pie_values( $data, $labels);
		//
		// Colours for each slice, in this case some of the colours
		// will be re-used (3 colurs for 5 slices means the last two
		// slices will have colours colour[0] and colour[1]):
		//
		$g->pie_slice_colours( array('#d01f3c','#356aa0','#aaccaa','#adffaa','#aa5500','#060606','#CCFF66') );
		
		$g->set_tool_tip( '#val# €' );
		
		$timestamp = mktime(0, 0, 0, $month, 1, $year);
		$s_month = date("F", $timestamp);
		$g->title( $s_month." ".$year, '{font-size:18px; color: #000000}' );
		
		// display the data
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
		$bar->key($st_lang['expenses_by_months'], 10);
		$bar->data = array();
		$months = array();
		while ($value = mysql_fetch_array($rows)) {
			$bar->data[] = $value[2];
			$timestamp = mktime(0, 0, 0, $value[1], 1, 2005);
			$months[] = date("M", $timestamp);
		}
		mysql_free_result($rows);
		
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