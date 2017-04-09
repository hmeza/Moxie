var filter = function() {
        var chosen = document.getElementById('category_search').selectedIndex;
        var redirect = document.getElementById('category_search').options[chosen].value;
        var redirect_string = (redirect == "0") ? "" : "/category/"+redirect;
        window.location="/expenses/index"+redirect_string+"/year/"+year+"/month/"+month;
};

var export_to_excel = function(){
	$('#search_form').find('[name=to_excel]').val(true);
	$('#search_submit').click();
};

var use_favourite_as_expense = function() {
	var id = $('#favourites').find(":selected").val();
	for (var i=0; i < favourite_data.length; i++) {
		if (id == favourite_data[i]["id"]) {
			$('#note').val(favourite_data[i]["note"]);
			$('#amount').val(-favourite_data[i]["amount"]);
			//$('#category[value="'+favourite_data[i]["category"]+'"]').val(favourite_data[i]["category"]);
			$('#category[value="'+favourite_data[i]["category"]+'"]').attr('selected', true);
			$('#category').chosen({
				disable_search_threshold: 10,
				width: "100%"
			});
			$('#tags').empty();
			var taggle = new Taggle('tags', {
				tags: favourite_data[i]["tags"],
				placeholder: tagsPlaceholder,
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
		}
	}
};

var load_favourites_data = function() {
	for (var i=0; i < favourite_data.length; i++) {
		$('#favourites').append('<option value="'+favourite_data[i]["id"]+'">'+favourite_data[i]["note"]+'</option>');
	}
};

$(document).ready(function() {
	$("#category").chosen({
		disable_search_threshold: 10,
		width: "100%"
	});

	$("#category_search").chosen({
		disable_search_threshold: 10,
		width: "100%"
	});


	var taggle = new Taggle('tags', {
			tags: tagList,
			placeholder: tagsPlaceholder,
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

	$('#tag_search').autocomplete({
		source: usedTagList
	});

	$("#export_to_excel_button").click(export_to_excel);

	load_favourites_data();
	$('#favourites').change(use_favourite_as_expense);
	$("#favourites_button").click(function (event) {
		$("#favourites_list").slideToggle();
	});
});