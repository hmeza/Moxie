<?php
// use the chart class to build the chart:
include_once( 'open-flash-chart.php' );

$data = explode(":",$_GET['mydata']);
$category = (empty($data[1])) ? 0 : $data[1];
$scale = (empty($category)) ? 2500 : 500;

mysql_connect("127.0.0.1","root","0nr3fn1");
mysql_select_db("moxie");
$sql = 'select YEAR(expense_date), MONTH(expense_date),sum(amount)'
.' from expenses'
.' where in_sum = 1';
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
?>
