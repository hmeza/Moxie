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
});