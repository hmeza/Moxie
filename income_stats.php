<?php
// use the chart class to build the chart:
include_once( 'application/3rdparty/ofc/open-flash-chart.php' );
$data = explode(":",$_GET['mydata']);
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
?>
