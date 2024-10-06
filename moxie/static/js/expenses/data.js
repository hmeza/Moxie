let filter = function () {
    let chosen = document.getElementById('category_search').selectedIndex;
    let redirect = document.getElementById('category_search').options[chosen].value;
    let redirect_string = (redirect == "0") ? "" : "/category/" + redirect;
    window.location = "/expenses/index" + redirect_string + "/year/" + year + "/month/" + month;
};

const export_to_excel = function () {
    let searchForm = $('#search_form');
    searchForm
        .find('form')
        .first()
        .append("<input type='hidden' name='to_excel' id='to_excel' value='true'>")
    $('#submit-id-filter').click();
    searchForm.find('#to_excel').remove();
};

function set_tags(row) {
    try {
        let tags = $('#id_tags');
        tags.tagsinput('removeAll');
        for (let j = 0; j < row["tags"].length; j++) {
            tags.tagsinput('add', row["tags"][j]);
        }
    } catch (e) {
        console.log(e);
    }
}

const use_favourite_as_expense = function () {
    let id = $('#id_favourites').find(":selected").val();
    let note = $('#id_note');
    let amount = $('#id_amount');
    if (id == "0") {
        amount.val(null);
        note.val('');
        $('#id_tags').tagsinput('removeAll');
        return;
    }
    let row = favourite_data[id];
    note.val(row["note"]);
    amount.val(-row["amount"]);
    let c = $('#id_category');
    let option_string = 'option[value="' + row["category"] + '"]';
    c.find('option[selected="selected"]').attr('selected', false);
    let opt = c.find(option_string);
    c.val(row["category"]);
    opt.attr("selected", "selected");
    opt.prop("selected", "selected");
    let in_sum = row["in_sum"] == "1";
    $('#id_in_sum').prop("checked", in_sum);
    set_tags(row);
};

function get_tags_object() {
    let tags = new Bloodhound({
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
    let tagsSelector = $('#id_tag');
    $("#login").submit(function (e) {
        let items_array = tagsSelector.tagsinput('items');
        tagsSelector.val(items_array);
        return true;
    });

    $("#export_to_excel_button").click(export_to_excel);

    $('#id_favourites').change(use_favourite_as_expense);

    let tags = get_tags_object();
    let tagsSearch = get_tags_object();

    // data-role must be set here
    tagsSelector.attr('data-role', 'tagsinput');
    tagsSelector.tagsinput({
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

    let tagSearchSelector = $('#id_tag_search');
    tagSearchSelector.attr('data-role', 'tagsinput');
    tagSearchSelector.tagsinput({
        typeaheadjs: [{
            minLength: 1,
            highlight: true,
        }, {
            minlength: 1,
            name: 'tag_search',
            displayKey: 'name',
            valueKey: 'name',
            source: tagsSearch.ttAdapter(),
            hint: true
        }],
        freeInput: true
    });
    let amount = $('#id_amount');
    /*amount.attr('inputmode', 'decimal');
    amount.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');
    let amount_min = $('#amount_min');
    amount_min.attr('inputmode', 'decimal');
    amount_min.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');
    let amount_max = $('#amount_max');
    amount_max.attr('inputmode', 'decimal');
    amount_max.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');*/

    // TODO do this modifying form classes
    let expenseDeleteSelector = $('#expense_delete');
    if(expenseDeleteSelector !== undefined) {
        let submitParent = $('#submit-id-submit').parent();
        submitParent.addClass('text-right');
        let firstSibling = submitParent.siblings().first();
        firstSibling.append(expenseDeleteSelector);
        firstSibling.removeClass('')
    }
    $('#search_form :input[type="submit"]').parent().addClass('text-right')

    $('#expenses_form').submit(function(e) {
        amount.val(amount.val().replace(",", "."));
    });
});
var elem;