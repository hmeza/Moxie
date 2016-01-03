var chart;
// Load the Visualization API and the piechart package.
google.load('visualization', '1.0', {'packages':['corechart']});
// Set a callback to run when the Google Visualization API is loaded.
google.setOnLoadCallback(drawChart);

var expensesPieFilter = function(chart, data) {
    var myFilter = data.getValue(chart.getSelection()[0].row, 0);
    if($("#category_filter option").filter(":contains('"+myFilter+"')").length > 0) {
        select_option = $("#category_filter option").filter(":contains('"+myFilter+"')").first().attr('value');;
        $("#category_filter").find('option[value="'+select_option+'"]').attr('selected', true);
        filter();
    }
}

var expensesBarRedirector = function(chart, data) {
    var months = {Jan:1, Feb:2, Mar:3, Apr:4, May:5, Jun:6, Jul:7, Aug:8, Sep:9, Oct:10, Nov:11, Dec:12};
    var targetMonthString = data.getValue(chart.getSelection()[0].row, 0);
    var targetMonth = months[targetMonthString];

    var objDate = new Date(),
        locale = "en-us",
        month = objDate.toLocaleString(locale, { month: "short" });
    currentMonth = months[month];
    targetYear = objDate.getFullYear();

    if(currentMonth <= targetMonth) {
        targetYear -= 1;
    }
    window.location.href = "https://moxie.dootic.com/expenses/index/month/"+targetMonth+"/year/"+targetYear;
}


/**
 * Sets the double click handler for google chart pie.
 * @param chart
 * @param data
 */
function addDoubleClickListener(chart, data, filterFunction) {
    var firstClick = 0;
    var secondClick = 0;

    google.visualization.events.addListener(chart, 'click', function () {
        var date = new Date();
        var millis = date.getTime();

        if (millis - secondClick > 1000) {
            // add delayed check if a single click occured
            setTimeout(function() {
                // no second click fast enough, it is a single click
                if (secondClick == 0) {
                    //console.log("single click");
                }
            }, 250);
        }

        // try to measure if double-clicked
        if (millis - firstClick < 250) {
            firstClick = 0;
            secondClick = millis;

            filterFunction(chart, data);

        } else {
            firstClick = millis;
            secondClick = 0;
        }
    });
}

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
    addDoubleClickListener(chart, data, expensesPieFilter);
    chart.draw(data, options);


    var data2 = google.visualization.arrayToDataTable(barChartData);
    width -= 100;
	var options2 = {
		title: barTitle,
		hAxis: {title: 'Meses', titleTextStyle: {color: 'red'}},
        vAxis: { viewWindow: { min: 0}},
		chartArea:{width:width}
	};

	var chart2 = new google.visualization.ColumnChart(document.getElementById('expenses_all'));
    addDoubleClickListener(chart2, data2, expensesBarRedirector);
	chart2.draw(data2, options2);
}

$(window).resize(function() {
    drawChart();
});
