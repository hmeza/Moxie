<?php
$month = (empty($_GET['month'])) ? date('n') : $_GET['month'];
$year = (empty($_GET['year'])) ? date('Y') : $_GET['year'];

// use the chart class to build the chart:
include_once( 'open-flash-chart.php' );
$g = new graph();

$g->bg_colour = '#ffffff';
// Spoon sales, March 2007
$g->pie(60,'#000000','{font-size: 12px; color: #000000;');
//
// pass in two arrays, one of data, the other data labels
// get data from DB
// get categories from DB
mysql_connect("127.0.0.1","root","0nr3fn1");
mysql_select_db("moxie");
$sql = "select sum(e.amount),c.name from expenses e left join categories c on c.id = e.category where e.user_owner = 1 group by e.category";
$sql = "select c0.id as parent_id, c.name as name,sum(e.amount) from expenses e, categories c left join categories c2 on c.id = c2.parent left join categories c0 on c0.id = c.parent "
	." where (c.id = e.category or c2.id = e.category) AND YEAR(e.expense_date) = ".$year." AND MONTH(e.expense_date) = ".$month
	." and e.in_sum = 1"
	." group by (c.id) order by c.id, c2.id";
$rows = mysql_query($sql);

$data = array();
$labels = array();
while ($value = mysql_fetch_array($rows)) {
	if ($value['parent_id'] == 1) {
		$data[] = $value[2];
		$labels[] = $value[1];
	}
}
mysql_free_result($rows);

$g->pie_values( $data, $labels);
//
// Colours for each slice, in this case some of the colours
// will be re-used (3 colurs for 5 slices means the last two
// slices will have colours colour[0] and colour[1]):
//
$g->pie_slice_colours( array('#d01f3c','#356aa0','#aaccaa','#adffaa','#224466') );

$g->set_tool_tip( '#val# €' );

$timestamp = mktime(0, 0, 0, $month, 1, $year);
$s_month = date("F", $timestamp);
$g->title( $s_month." ".date('Y'), '{font-size:18px; color: #000000}' );

// display the data
echo $g->render();
?>