document.addEventListener("DOMContentLoaded", function (event) {

    /**
     * Send email address (and other data) to Pardot
     */
    $('form.pardot-submit').submit(function(event) {
        event.preventDefault();

        var email = $("form.pardot-submit input.pardot-email").val();
        if (email.length == 0) {
            return;
        }
        var data = { "email": email };

        var otherFields = $("input.pardot-field");
        if (otherFields.length > 0) {
            otherFields.each(function(index, element) {
                data[$(this).attr('name')] = $(this).val();
            });
        }

        $.ajax({
            url: "/api/pardot-email",
            method: "POST",
            data: JSON.stringify(data),
            complete: function(){
                $("form.pardot-submit").submit();
            }
        });
    });

});
