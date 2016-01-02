function markLine(id) {
	window.location="/expenses/markline/id/"+id;
}

function filter() {
	var chosen = document.getElementById('category_filter').selectedIndex;
	var redirect = document.getElementById('category_filter').options[chosen].value;
	window.location="/expenses/index/category_filter/"+redirect+"/year/"+year+"/month/"+month;
}

$(document).ready(function() {
    enableSelectBoxes();
});

function enableSelectBoxes() {
	$('div.selectBox').each(function () {
		$(this).children('span.selected').html($(this).children('div.selectOptions').children('span.selectOption:first').html());
		$(this).attr('value', $(this).children('div.selectOptions').children('span.selectOption:first').attr('value'));

		$(this).children('span.selected,span.selectArrow').click(function () {
			if ($(this).parent().children('div.selectOptions').css('display') == 'none') {
				$(this).parent().children('div.selectOptions').css('display', 'block');
			}
			else {
				$(this).parent().children('div.selectOptions').css('display', 'none');
			}
		});

		$(this).find('span.selectOption').click(function () {
			$(this).parent().css('display', 'none');
			$(this).closest('div.selectBox').attr('value', $(this).attr('value'));
			$(this).parent().siblings('span.selected').html($(this).html());
			$('#category').val($(this).attr('value'));
		});
	});

	if ($(".selectedSpan")[0]) {
		spanText = $(".selectedSpan").text();
		spanValue = $(".selectedSpan").attr('value');
		$(".selected").text(spanText);
		$('#category').val(spanValue);
	}
}

function useExpense(category, note) {
	$('#note').val(note);
	$('#category').val(category);
	$('.selectOption').each(function(e, span) {
		if($(this).attr('value') == category) {
			$(this).parent().siblings('span.selected').html($(this).html());
		}
	});
}