function markLine(id) {
	window.location="/expenses/markline/id/"+id;
}

var filter = function() {
        var chosen = document.getElementById('category_search').selectedIndex;
        var redirect = document.getElementById('category_search').options[chosen].value;
        var redirect_string = (redirect == "0") ? "" : "/category/"+redirect;
        window.location="/expenses/index"+redirect_string+"/year/"+year+"/month/"+month;
};

$(document).ready(function() {
	// @todo check if search form exists and if there is a date field
	$('#date_min').attr('type', 'date');
	$('#date_max').attr('type', 'date');

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
	})
});