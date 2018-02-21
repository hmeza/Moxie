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
	if (id == "0") {
		$('#amount').val(null);
		$('#note').val('');
		$('#tags').tagsinput('removeAll');
		// todo set current date
		return;
	}
	for (var i=0; i < favourite_data.length; i++) {
		if (id == favourite_data[i]["id"]) {
			$('#note').val(favourite_data[i]["note"]);
			$('#amount').val(-favourite_data[i]["amount"]);
			for(var j=0; j < favourite_data[i]["tags"].length; j++) {
				$('#tags').tagsinput('add', favourite_data[i]["tags"][j]);
			}
			var c = $('#category');
			var favourite_category = favourite_data[i]["category"];
			var option_string = 'option[value="'+ favourite_category +'"]';
			c.find('option[selected="selected"]').attr('selected', false);

			//c.find(option_string).attr('selected', 'selected');
			var op_str = c.find(option_string);
			//alert("option string selected" + option_string);
			op_str.attr("selected", "selected");

			c.selectmenu("refresh");
/*			var current = null;
            for (j = 0; j < c.length; j++) {
                current = options[i];
                if (current.selected === true && !current.hasAttribute('selected')) {
                    options[i].setAttribute('selected', '');
                }
                if (current.selected === false && current.hasAttribute('selected')) {
                    options[i].removeAttribute('selected');
                }
            }*/
            break;
		}
	}
};

$(document).ready(function() {
	$("#login").submit(function(e) {
	     var self = this;
	     var items_array = $("#tags").tagsinput('items');
	     $("#tags").val(items_array);
	     return true;
	});

	$("#export_to_excel_button").click(export_to_excel);

	$('#favourites').change(use_favourite_as_expense);

	var tags = new Bloodhound({
	    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
	    queryTokenizer: Bloodhound.tokenizers.whitespace,
	    local: $.map(usedTagList, function (tag) {
	        return {
	            name: tag
	        };
	    })
	});
	tags.initialize();

	// data-role must be set here
	$('#tags').attr('data-role', 'tagsinput');
	$('#tags').tagsinput({
	    typeaheadjs: [{
	          minLength: 1,
	          highlight: true,
	    },{
	        minlength: 1,
	        name: 'tags',
	        displayKey: 'name',
	        valueKey: 'name',
	        source: tags.ttAdapter(),
		hint: true
	    }],
	    freeInput: true
	});
});
