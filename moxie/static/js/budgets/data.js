$(document).ready(function() {
    $(document).ajaxStart(function () {
        $("#spinner").show();
    })
    .ajaxStop(function () {
        $("#spinner").hide();
    });
});

function store(triggerId) {
    let category = $("#category" + triggerId).val();
    let amount = $("#amount" + triggerId).val();
    let url = categoryBudgetUrl.replace("1", category);
    $.ajax({
        method: 'POST',
        headers: {'X-CSRFToken': $('input[name="csrfmiddlewaretoken"]').val()},
        url: url,
        data: {
            'amount': amount
        },
        success: function() {
            $('#budget_message').html(successUpdatingBudget);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $('#budget_message').html(errorUpdatingBudget);
        }
    })
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