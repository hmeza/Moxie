function getSum() {
    var sum = 0;
    $('[id^=amount]').each(function() {
        sum += parseFloat(this.value);
    });
    sum = Math.round(sum * 100) / 100;
    document.getElementById('sum').innerHTML = '<b>' + sum + ' &euro;</b>';
}

function snapshot() {
    $.ajax({url: '/budgets/snapshot',
        type: 'GET',
        data: '',
        success: function(data) {
            // show OK message
            $('#message').html(message);
        }}
    );
}