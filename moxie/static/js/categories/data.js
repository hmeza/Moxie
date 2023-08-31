function stopCallback(event, ui) {
    // get all inputs inside categories_list and send
    const category_list = $('#categories_list');
    let categories = {};
    category_list.find('input').each(function(index) {
        categories[index] = $(this).attr('name');
    });

    $.ajax({
        url: categoryOrderUrl,
        type: "POST",
        dataType: 'json',
        headers: {'X-CSRFToken': $('input[name="csrfmiddlewaretoken"]').val()},
        data: categories
    });
}

$(document).ready(function() {
    let cat_list = $('#categories_list');
    cat_list.sortable({
        delay: 100,
        stop:  stopCallback
    });
    cat_list.disableSelection();
});