var chart;
// Load the Visualization API and the piechart package.
google.load('visualization', '1.0', {'packages':['corechart']});
// Set a callback to run when the Google Visualization API is loaded.
google.setOnLoadCallback(drawChart);
// Callback function
function drawChart() {
    var data = google.visualization.arrayToDataTable(barChartData);
    
	var options = {
		title: barTitle,
		hAxis: {title: barYearLabel, titleTextStyle: {color: 'green'}},
		vAxis: { viewWindow: { min: 0}}
	};

	var chart = new google.visualization.ColumnChart(document.getElementById('incomes_all'));
	chart.draw(data, options);
}
