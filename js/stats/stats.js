function onmouseoverchange(month) {
	for (category in categories) {
		$("#budget"+category).html((budgets[month][categories[category]].toFixed(2))+" &euro;");
	}
}

function onmouseoutchange() {
	for (category in categories) {
		$("#budget"+category).html((budgets[budget_index][categories[category]].toFixed(2))+" &euro;");
	}
}

$(document).ready(function() {
	$("#show_stats").click(
			function(event) {
				$("#box").slideToggle();
			}
	);
	$("#show_budget").click(
			function(event) {
				$("#budget").slideToggle();
			}
	);
	$("#caja").click(function(event) {
		$("#box").slideUp();
	});
	$("[name^='month']").mouseover(function() {
		var monthId = $(this).attr('name').replace('month', '');
		onmouseoverchange(monthId);
	});
	$("[name^='month']").mouseout(function() {
		var monthId = $(this).attr('name').replace('month', '');
		onmouseoutchange(monthId);
	});
});