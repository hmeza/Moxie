let filter = function () {
    let chosen = document.getElementById('category_search').selectedIndex;
    let redirect = document.getElementById('category_search').options[chosen].value;
    let redirect_string = (redirect == "0") ? "" : "/category/" + redirect;
    window.location = "/expenses/index" + redirect_string + "/year/" + year + "/month/" + month;
};

const export_to_excel = function () {
    $('#search_form').find('[name=to_excel]').val(true);
    $('#search_submit').click();
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
    $("#login").submit(function (e) {
        let self = this;
        let items_array = $("#id_tags").tagsinput('items');
        $("#id_tags").val(items_array);
        return true;
    });

    $("#export_to_excel_button").click(export_to_excel);

    $('#id_favourites').change(use_favourite_as_expense);

    tags = get_tags_object();
    tags_search = get_tags_object();

    // data-role must be set here
    $('#id_tags').attr('data-role', 'tagsinput');
    $('#id_tags').tagsinput({
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
    $('#id_tag_search').attr('data-role', 'tagsinput');
    $('#id_tag_search').tagsinput({
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

    // TODO do this modifying form classes
    let expenseDeleteSelector = $('#expense_delete');
    if(expenseDeleteSelector !== undefined) {
        let submitParent = $('#submit-id-submit').parent();
        submitParent.addClass('text-right');
        submitParent.siblings().first().append(expenseDeleteSelector);
    }
    $('#search_form :input[type="submit"]').parent().addClass('text-right')
});
