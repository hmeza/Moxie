var chart;
// Load the Visualization API and the piechart package.
google.load('visualization', '1.0', {'packages':['corechart']});
// Set a callback to run when the Google Visualization API is loaded.
google.setOnLoadCallback(drawChart);

var expensesPieFilter = function(chart, data) {
    var myFilter = data.getValue(chart.getSelection()[0].row, 0);
    if($("#category_search option").filter(":contains('"+myFilter+"')").length > 0) {
        select_option = $("#category_search option").filter(":contains('"+myFilter+"')").first().attr('value');;
        $("#category_search").find('option[value="'+select_option+'"]').attr('selected', true);
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
    window.location.href = baseUrl + "/expenses/index/month/"+targetMonth+"/year/"+targetYear;
}


function addTypeDisableListener(columnChart, data2ToDataTable, columnChartData, columnChartOptions) {
    var columns = [];
    var series = {};
    for (var i = 0; i < data2ToDataTable.getNumberOfColumns(); i++) {
        columns.push(i);
        if (i > 0) {
            series[i - 1] = {};
        }
    }
    google.visualization.events.addListener(columnChart, 'select', function () {
        var sel = columnChart.getSelection();
        // if selection length is 0, we deselected an element
        if (sel.length > 0) {
            // if row is undefined, we clicked on the legend
            if (sel[0].row === null) {
                var col = sel[0].column;
                if (columns[col] == col) {
                    // hide the data series
                    columns[col] = {
                        label: data2ToDataTable.getColumnLabel(col),
                        type: data2ToDataTable.getColumnType(col),
                        calc: function () {
                            return null;
                        },
                    };

                    // grey out the legend entry
                    series[col - 1].color = '#CCCCCC';
                }
                else {
                    // show the data series
                    columns[col] = col;
                    series[col - 1].color = null;
                }
                data2ToDataTable = new google.visualization.DataView(columnChartData);
                data2ToDataTable.setColumns(columns);
                columnChart.draw(data2ToDataTable, columnChartOptions);
            }
        }
    });
    return data2ToDataTable;
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


    var columnData = google.visualization.arrayToDataTable(barChartData);
    width -= 100;
	var columnGraphOptions = {
		title: barTitle,
		hAxis: {title: 'Meses', titleTextStyle: {color: 'red'}},
        vAxis: { viewWindow: { min: 0}},
		chartArea:{width:width},
        isStacked: true,
        legend: {position: 'top'},
        is3D: true
	};
	var columnChart = new google.visualization.ColumnChart(document.getElementById('expenses_all'));
    var view = new google.visualization.DataView(columnData);
    var data2ToDataTable = view.toDataTable();

    columnChart.draw(data2ToDataTable, columnGraphOptions);

    addDoubleClickListener(columnChart, data2ToDataTable, expensesBarRedirector);
    addTypeDisableListener(columnChart, data2ToDataTable, columnData, columnGraphOptions);
}

$(window).resize(function() {
    drawChart();
});
