if (typeof Clipboard == 'function') {
	new Clipboard('.btn', {
	  text: function() {
		  console.log("copying to clipboard");
	    return window.location.href;
	  }
	});
}

$(document).ready(function() {
	$("#add_user_button").click(function (event) {
		$("#add_user_form").slideToggle();
	});
	
	$("#sheet_id_redirector").chosen({
		disable_search_threshold: 10,
		width: "100%"
	});
	
	$("#id_sheet_user").chosen({
		disable_search_threshold: 10,
		width: "100%"
	});
	
	$('#sheet_id_redirector').on('change', function(e, params) {
		unique_id = e.target.value;
		window.location.replace("/sheets/view/id/" + unique_id);
	});
});