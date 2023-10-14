let trendsRedirector = function(chart, data) {
    let selected = chart.getSelection().valueOf()[0].row;
    let targetDate = data.Lf[selected].c[0].v.split("/");
    let targetMonth = parseInt(targetDate[0]);
    let targetYear = parseInt(targetDate[1]);
    let category = $('#trends_category').val();
    window.location.href = "/expenses/index/category/"+category+"/month/"+targetMonth+"/year/"+targetYear;
};

google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
let chart;
function drawChart() {
    let category = $('select[name=trends_category]').val();
    if(typeof(category) === 'undefined') {
        category = default_category;
    }
    // let trends_sub_array = trends_array.find((e) => e.pk == category);
    // let trends_sub_array = trends_array[category];
    // let header = [];
    // header.push(["Mes/año", trends_sub_array['name']]);
    // for(let i=0; i<trends_sub_array['data'].length;i++) {
    //     let el = trends_sub_array['data'][i];
    //     header = header.push([el.month+"/"+el.year, el.amount])
    // }
    // console.log(header);
    let data = google.visualization.arrayToDataTable(trends_array[category]);

    let options = {
        title: '',
        hAxis: {
            //title: 'Mes/año',
            titleTextStyle: {color: '#333'},
            slantedText:true,
            slantedTextAngle:10,
            type: 'string'
        },
        vAxis: {minValue: 0},
        legend: {position: 'top'}
    };

    chart = new google.visualization.AreaChart(document.getElementById('stats_trends'));
    chart.draw(data, options);
    addDoubleClickListener(chart, data, trendsRedirector);
}