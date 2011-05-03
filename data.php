<?php
$data = explode(":",$_GET['mydata']);
$month = (empty($data[1])) ? date('n') : $data[1];
$year = (empty($data[3])) ? date('Y') : $data[3];
$user = (empty($data[5])) ? 0 : $data[5]; 
// use the chart class to build the chart:
include_once( 'application/3rdparty/ofc/open-flash-chart.php' );
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

$sql = "select c0.id as parent_id, c.name as name,sum(e.amount) from expenses e, categories c left join categories c2 on c.id = c2.parent left join categories c0 on c0.id = c.parent "
	." where (c.id = e.category or c2.id = e.category) AND YEAR(e.expense_date) = ".$year." AND MONTH(e.expense_date) = ".$month
	." and e.in_sum = 1 and e.user_owner = ".$user
	." group by (c.id) order by c.id, c2.id";
$rows = mysql_query($sql);

$data = array();
$labels = array();
while ($value = mysql_fetch_array($rows)) {
	if ($value['parent_id'] == $i_id) {
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
$g->pie_slice_colours( array('#d01f3c','#356aa0','#aaccaa','#adffaa','#aa5500','#060606','#CCFF66') );

$g->set_tool_tip( '#val# €' );

$timestamp = mktime(0, 0, 0, $month, 1, $year);
$s_month = date("F", $timestamp);
$g->title( $s_month." ".$year, '{font-size:18px; color: #000000}' );

// display the data
echo $g->render();
?>
