function copyToClipboard(){
  let copyText = document.getElementById("clipboard-element");
  navigator.clipboard.writeText(copyText.getAttribute('data-clipboard-text'));
}

function changeSelectors() {
    let val = $('#id_category').val();
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
});