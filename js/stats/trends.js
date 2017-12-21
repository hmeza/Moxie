var trendsRedirector = function(chart, data) {
    var selected = chart.getSelection().valueOf()[0].row;
    var targetDate = data.Lf[selected].c[0].v.split("/");
    var targetMonth = parseInt(targetDate[0]);
    var targetYear = parseInt(targetDate[1]);
    var category = $('#trends_category').val();
    window.location.href = moxie_url+"/expenses/index/category/"+category+"/month/"+targetMonth+"/year/"+targetYear;
};

google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
var chart;
function drawChart() {
    var category = $('select[name=trends_category]').val();
    if(typeof(category) === 'undefined') {
        category = default_category;
    }
    var data = google.visualization.arrayToDataTable(trends_array[category]);

    var options = {
        title: '',
        hAxis: {
            //title: 'Mes/año',
            titleTextStyle: {color: '#333'},
            slantedText:true,
            slantedTextAngle:38,
        },
        vAxis: {minValue: 0},
        legend: {position: 'top'}
    };

    chart = new google.visualization.AreaChart(document.getElementById('stats_trends'));
    chart.draw(data, options);
    addDoubleClickListener(chart, data, trendsRedirector);
}