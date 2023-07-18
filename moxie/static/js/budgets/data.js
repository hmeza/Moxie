$(document).ready(function() {
    $(document).ajaxStart(function () {
        $("#spinner").show();
    })
    .ajaxStop(function () {
        $("#spinner").hide();
    });
});

function store(trigger_id) {
    let category = $("#category" + trigger_id).val();
    let amount = $("#amount" + trigger_id);
    $.ajax({
        url: "/budgets/add/category/" + category + "/amount/" + amount.val()
    });
}

function getSum(trigger_id) {
    let sum = 0;
    $('[id^=amount]').each(function() {
        sum += parseFloat(this.value);
    });
    sum = Math.round(sum * 100) / 100;
    document.getElementById('sum').innerHTML = '<b>' + sum + ' &euro;</b>';
    store(trigger_id);
}

function snapshot() {
    $.ajax({url: '/budgets/snapshot',
        type: 'GET',
        data: '',
        success: function(data) {
            $('#message').html(message);
        }}
    );
}