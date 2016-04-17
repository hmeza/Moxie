var filter = function() {
        var chosen = document.getElementById('category_filter').selectedIndex;
        var redirect = document.getElementById('category_filter').options[chosen].value;
        var redirect_string = (redirect == "0") ? "" : "/category/"+redirect;
        window.location="/incomes/index"+redirect_string;
}

$(document).ready(function() {
    $("#category").chosen({
        disable_search_threshold: 10,
        width: "100%"
    });

    // selecct category and tag, if any
    $('#category_filter').val(category);
//    $('#tag_filter').val(tag);
    // handle here select changes
    $('#category_filter').change(filter);
//    $('#tag_filter').change(filter_tag);
});
