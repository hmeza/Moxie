var moxieRedirector = function() {
    this.redirect = function (url) {
        window.location = url;
    };
};

function confirmDelete(id) {
    var response;

    response = confirm(deleteMessage);
    if (response == true) {
        moxieRedirector.redirect(deleteUrl + id);
        //window.location = deleteUrl + id;
    }
}
