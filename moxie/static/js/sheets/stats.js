google.load('visualization', '1.0', {'packages':['corechart']});

// Callback function
function drawChart() {
	// Create the data table.
	let data = new google.visualization.DataTable();
    data.addColumn('string', 'User');
    data.addColumn('number', '€');
    data.addRows(pieData);
    let formatter = new google.visualization.NumberFormat(
    		{suffix: ' €', negativeColor: 'red', negativeParens: true}
    );
    formatter.format(data, 1);

    let width = $('.moxie-right').width();
    let height = 0.61 * width;
    let fontsize = width > 450 ? 16 : 12;
    let options = {
    		'title':pieTitle,
            'height': height ,
            is3D: true,
    		legend: {position: 'right', textStyle: {color: 'black', fontSize: fontsize}},
    		titleTextStyle: {color: 'black', fontSize: 25}
    };
    // Instantiate and draw our chart, passing in some options.
    chart = new google.visualization.PieChart(document.getElementById('sheet_distribution'));
    chart.draw(data, options);
}

$(window).resize(function() {
    drawChart();
});
$(document).ready(function() {
    google.setOnLoadCallback(drawChart);
});