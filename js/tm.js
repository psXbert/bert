
function displayNotifications() {
    $.getJSON("/js/newswire.json", function (data) {
        $(".maincontent").prepend("<div id='notifications'></div>");
        $.each(data["notifications"], function (i, item) {
            $("#notifications").append('<div class="alert alert-info" role="alert"><strong>' + item.title + '</strong><br>' + item.text + '</div>');
        });
    })

            .fail(function (jqxhr, textStatus, error) {
                var err = textStatus + ", " + error;
                $("#notifications").append('<div class="alert alert-danger" role="alert"><strong>Error</strong><br>' + err + '</div>');
            });

}


$(document).ready(function () {
    displayNotifications();
});
