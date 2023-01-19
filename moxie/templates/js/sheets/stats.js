google.load('visualization', '1.0', {'packages':['corechart']});
// Callback function
function drawChart() {
	// Create the data table.
	var data = new google.visualization.DataTable();
    data.addColumn('string', 'User');
    data.addColumn('number', '€');
    data.addRows(pieData);
    var formatter = new google.visualization.NumberFormat(
    		{suffix: ' €', negativeColor: 'red', negativeParens: true}
    );
    formatter.format(data, 1);

    width = $('.moxie-right').width();
    height = 0.61 * width;
    fontsize = width > 450 ? 16 : 12;
    var options = {
    		'title':pieTitle,
            'height': height ,
            is3D: true,
    		legend: {position: 'right', textStyle: {color: 'black', fontSize: fontsize}},
    		titleTextStyle: {color: 'black', fontSize: 25}
    };
    // Instantiate and draw our chart, passing in some options.
    chart = new google.visualization.PieChart(document.getElementById('expenses_month'));
    chart.draw(data, options);
}

$(window).resize(function() {
    drawChart();
});
$(document).ready(function() {
	drawChart();
});