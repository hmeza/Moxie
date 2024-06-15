const export_to_excel = function () {
    let searchForm = $('#search_form');
    searchForm
        .find('form')
        .first()
        .append("<input type='hidden' name='to_excel' id='to_excel' value='true'>")
    $('#submit-id-filter').click();
    searchForm.find('#to_excel').remove();
};

$(document).ready(function() {
    let amount = $('#id_amount');
    /*amount.attr('inputmode', 'decimal');
    amount.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');*/
    $('#date_min').attr('type', 'date');
    $('#date_max').attr('type', 'date');

    // TODO do this modifying form classes
    let submitParent = $('#id-incomesForm :input[type="submit"]').parent();
    submitParent.addClass('text-right');
    let incomeDeleteSelector = $('#income_delete');
    if(incomeDeleteSelector !== undefined) {
        submitParent.siblings().first().append(incomeDeleteSelector);
    }
    $('#search_form :input[type="submit"]').parent().addClass('text-right')
    $('#to').click(export_to_excel);

    $('#id-incomesForm').submit(function(e) {
        amount.val(amount.val().replace(",", "."));
    });
});
