$(document).ready(function() {
    $('#date_min').attr('type', 'date');
    $('#date_max').attr('type', 'date');

    $("#category").chosen({
        disable_search_threshold: 10,
        width: "100%"
    });

    $("#category_search").chosen({
        disable_search_threshold: 10,
        width: "100%"
    });
});
