let moxieRedirector = {
	'redirect': function(url) {
		window.location = url;
	}
};

function confirmDelete(id) {
    let response;

    response = confirm(deleteMessage);
    if (response == true) {
        moxieRedirector.redirect(deleteUrl + id);
    }
}

/**
 * Sets the double click handler for google charts.
 * @param chart
 * @param data
 * @param filterFunction
 */
function addDoubleClickListener(chart, data, filterFunction) {
    let firstClick = 0;
    let secondClick = 0;

    google.visualization.events.addListener(chart, 'click', function () {
        let date = new Date();
        let millis = date.getTime();

        if (millis - secondClick > 1000) {
            // add delayed check if a single click occured
            setTimeout(function() {
                // no second click fast enough, it is a single click
                if (secondClick == 0) {
                    //console.log("single click");
                }
            }, 250);
        }

        // try to measure if double-clicked
        if (millis - firstClick < 250) {
            firstClick = 0;
            secondClick = millis;

            filterFunction(chart, data);

        } else {
            firstClick = millis;
            secondClick = 0;
        }
    });
}

/**
 * Add data-target="#toggableDiv" and class toggable to any button
 * to toggle div and change button color, and collapse the rest of
 * the toggable divs.
 */
function enableToggableButtons() {
    $('.toggable ').each(function (e) {
        $(this).click(function (e) {
            $('.toggable').not(this).each(function (e) {
                let button = $(this);
                button.removeClass('btn-primary');
                button.addClass('btn-info');
                let collapse = button.attr('data-target');
                $(collapse).hide();
            });
            $(this).removeClass('btn-info');
            $(this).addClass('btn-primary');
            let collapse = $(this).attr('data-target');
            $(collapse).show();
        });
    });
}

$(document).ready(function() {
    enableToggableButtons();
});
