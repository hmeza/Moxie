var chart;
// Load the Visualization API and the piechart package.
google.load('visualization', '1.0', {'packages':['corechart']});
// Set a callback to run when the Google Visualization API is loaded.
google.setOnLoadCallback(drawChart);
// Callback function
function drawChart() {
	// Create the data table.
	var data = new google.visualization.DataTable();
    data.addColumn('string', 'Category');
    data.addColumn('number', '€');
    data.addRows(pieData);
    var formatter = new google.visualization.NumberFormat(
    		{suffix: ' €', negativeColor: 'red', negativeParens: true}
    );
    formatter.format(data, 1);
    // Set chart options
    // width 500|800
    // height 300|500
    // fontsize none|25

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
    // TODO: Add listener for category_filter
    google.visualization.events.addListener(chart, 'select', function() {
        var filter = data.getValue(chart.getSelection()[0].row, 0);
        console.log(filter);
        $('#category_filter').find('option[text="'+filter+'"]').attr('selected', true);
    });
    chart.draw(data, options);


    var data2 = google.visualization.arrayToDataTable(barChartData);
    width -= 100;
	var options2 = {
		title: barTitle,
		hAxis: {title: 'Meses', titleTextStyle: {color: 'red'}},
		chartArea:{width:width}
	};

	var chart2 = new google.visualization.ColumnChart(document.getElementById('expenses_all'));
	chart2.draw(data2, options2);
}

$(window).resize(function() {
    drawChart();
});