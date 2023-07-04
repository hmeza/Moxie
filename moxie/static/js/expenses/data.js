var filter = function () {
    var chosen = document.getElementById('category_search').selectedIndex;
    var redirect = document.getElementById('category_search').options[chosen].value;
    var redirect_string = (redirect == "0") ? "" : "/category/" + redirect;
    window.location = "/expenses/index" + redirect_string + "/year/" + year + "/month/" + month;
};

var export_to_excel = function () {
    $('#search_form').find('[name=to_excel]').val(true);
    $('#search_submit').click();
};

var use_favourite_as_expense = function () {
    var id = $('#favourites').find(":selected").val();
    if (id == "0") {
        $('#amount').val(null);
        $('#note').val('');
        $('#tags').tagsinput('removeAll');
        return;
    }
    for (var i = 0; i < favourite_data.length; i++) {
        if (id == favourite_data[i]["id"]) {
            row = favourite_data[i];
            $('#note').val(row["note"]);
            $('#amount').val(-row["amount"]);
            var tags = $('#tags');
            tags.tagsinput('removeAll');
            for (var j = 0; j < row["tags"].length; j++) {
                tags.tagsinput('add', row["tags"][j]);
            }
            var c = $('#category');
            var favourite_category = row["category"];
            var option_string = 'option[value="' + favourite_category + '"]';
            c.find('option[selected="selected"]').attr('selected', false);
            var opt = c.find(option_string);
            c.val(row["category"]);
            opt.attr("selected", "selected");
            opt.prop("selected", "selected");
            var in_sum = row["in_sum"] == "1";
            $('#in_sum').prop("checked", in_sum);
            break;
        }
    }
};

function get_tags_object() {
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
    return tags;
}

$(document).ready(function () {
    $("#login").submit(function (e) {
        var self = this;
        var items_array = $("#tags").tagsinput('items');
        $("#tags").val(items_array);
        return true;
    });

    $("#export_to_excel_button").click(export_to_excel);

    $('#favourites').change(use_favourite_as_expense);

    tags = get_tags_object();
    tags_search = get_tags_object();

    // data-role must be set here
    $('#tags').attr('data-role', 'tagsinput');
    $('#tags').tagsinput({
        typeaheadjs: [{
            minLength: 1,
            highlight: true,
        }, {
            minlength: 1,
            name: 'tags',
            displayKey: 'name',
            valueKey: 'name',
            source: tags.ttAdapter(),
            hint: true
        }],
        freeInput: true
    });
    $('#tag_search').attr('data-role', 'tagsinput');
    $('#tag_search').tagsinput({
        typeaheadjs: [{
            minLength: 1,
            highlight: true,
        }, {
            minlength: 1,
            name: 'tag_search',
            displayKey: 'name',
            valueKey: 'name',
            source: tags_search.ttAdapter(),
            hint: true
        }],
        freeInput: true
    });
    let amount = $('#amount');
    amount.attr('inputmode', 'decimal');
    amount.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');
    let amount_min = $('#amount_min');
    amount_min.attr('inputmode', 'decimal');
    amount_min.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');
    let amount_max = $('#amount_max');
    amount_max.attr('inputmode', 'decimal');
    amount_max.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');
});
