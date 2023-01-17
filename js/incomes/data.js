$(document).ready(function() {
    let amount = $('#amount');
    amount.attr('inputmode', 'numeric');
    amount.attr('pattern', '[-+]?[0-9]*[.,]?[0-9]+');
    $('#date_min').attr('type', 'date');
    $('#date_max').attr('type', 'date');
});
