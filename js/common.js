var moxieRedirector = {
	'redirect': function(url) {
		window.location = url;
	}
}

function confirmDelete(id) {
    var response;

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
    var firstClick = 0;
    var secondClick = 0;

    google.visualization.events.addListener(chart, 'click', function () {
        var date = new Date();
        var millis = date.getTime();

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