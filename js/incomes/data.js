$(document).ready(function() {
    let amount = $('#amount');
    amount.attr('type', 'number');
    amount.attr('pattern', '\d*\.,');
    $('#date_min').attr('type', 'date');
    $('#date_max').attr('type', 'date');
});
