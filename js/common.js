function confirmDelete(id) {
    var response;

    response = confirm(deleteMessage);
    if (response == true) {
        window.location = deleteUrl + id;
    }
}
