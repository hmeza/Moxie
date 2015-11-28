function markLine(id) {
	window.location="/expenses/markline/id/"+id;
}

function filter() {
	var chosen = document.getElementById('category_filter').selectedIndex;
	var redirect = document.getElementById('category_filter').options[chosen].value;
	window.location="/expenses/index/category_filter/"+redirect+"/year/"+year+"/month/"+month;
}

function confirmDelete(id) {
	var response;
	var name;
	var new_value = 0;
	var deleted_value = 0;

	response = confirm("Are you sure to delete this expense?");
	if (response == true) {
		name = id.split("/");
		$.ajax({
			type: "GET",
			data: "",
			url: dodeleteUrl+id,
		});

		new_value = $("#total").html().replace(" &euro;", "").replace(" €", "");
		deleted_value = id.split("/");
		deleted_value = deleted_value[0];
		deleted_value = $("#val"+deleted_value).html().replace(" &euro;", "").replace(" €", "");
		new_value = parseFloat(new_value) - parseFloat(deleted_value);
		$("#total").html(new_value.toFixed(2)+" &euro;");
		$("#tr"+name[0]).toggle(1000);

		$.ajax({
			type: "GET",
			url: getyearUrl
		}).done(function(data) {
			barChartData = eval(data);
			$.ajax({
				type: "GET",
				url: getmonthUrl
			}).done(function(data) {
				pieData = eval(data);
				drawChart();
			});
		});
	}
}