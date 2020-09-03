var cookiePreferences = JSON.parse(readCookie('cookie_preferences'));
var pardotSubmitted = false;

// excute pardot JS if user consents to marketing cookies
if (cookiePreferences !== null) {
  if (cookiePreferences.marketing === true) {
    document.addEventListener("DOMContentLoaded", function (event) {

        /**
         * Send email address (and other data) to Pardot
         */
        $('form.pardot-submit').submit(function(event) {

            if (pardotSubmitted) {
                return true;
            }

            console.log('Sending to Pardot.');

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
                    pardotSubmitted = true;
                    console.log('Proceeding with submission.');
                    $("form.pardot-submit button[type=submit]").click();
                }
            });
        });

    });

    }
}
