if (typeof Clipboard == 'function') {
	new Clipboard('.glyphicon-duplicate', {
	  text: function() {
		  console.log("copying to clipboard");
	    return window.location.href;
	  }
	});
}

function changeSelectors() {
    var val = $('#id_category').val();
    $('.sheet_categories_select').each(function(i, e) {
        $(this).val(val);
    });
}

$(document).ready(function() {
	$("#add_user_button").click(function (event) {
		$("#add_user_form").slideToggle();
	});
	
	$("#add_expense_button").click(function (event) {
		$("#add_expense_form").slideToggle();
	});
	
	$('#sheet_id_redirector').on('change', function(e, params) {
		unique_id = e.target.value;
		window.location.replace("/sheets/view/id/" + unique_id);
	});
});