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
    var options = {'title':'Month expense', 'width':500, 'height':300};
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

	var options2 = {
		title: barTitle,
		hAxis: {title: 'Year', titleTextStyle: {color: 'red'}}
	};

	var chart2 = new google.visualization.ColumnChart(document.getElementById('expenses_all'));
	chart2.draw(data2, options2);
}