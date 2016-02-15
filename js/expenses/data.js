function markLine(id) {
	window.location="/expenses/markline/id/"+id;
}

var filter = function() {
        var chosen = document.getElementById('category_filter').selectedIndex;
        var redirect = document.getElementById('category_filter').options[chosen].value;
        var redirect_string = (redirect == "0") ? "" : "/category/"+redirect;
        window.location="/expenses/index"+redirect_string+"/year/"+year+"/month/"+month;
}

var filter_tag = function() {
        var chosen = document.getElementById('tag_filter').selectedIndex;
        var value = document.getElementById('tag_filter').options[chosen].value;
        var redirect = document.getElementById('tag_filter').options[chosen].text;
        var redirect_string = (value == "0") ? "" : "/tag/"+redirect;
        window.location="/expenses/index"+redirect_string+"/year/"+year+"/month/"+month;
}

$(document).ready(function() {
    enableSelectBoxes();

	var taggle = new Taggle('tags', {
			tags: tagList,
			duplicateTagClass: 'bounce'
	});

	var container = taggle.getContainer();
	var input = taggle.getInput();

	$(input).autocomplete({
		source: usedTagList,
		appendTo: container,
		position: { at: "left bottom", of: container },
		select: function(event, data) {
			event.preventDefault();
			//Add the tag if user clicks
			if (event.which === 1) {
				taggle.add(data.item.value);
			}
		}
	});

    // selecct category and tag, if any
    $('#category_filter').val(category);
    $('#tag_filter').val(tag);
    // handle here select changes
    $('#category_filter').change(filter);
    $('#tag_filter').change(filter_tag);
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
