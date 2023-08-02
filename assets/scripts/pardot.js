var cookiePreferences = JSON.parse(decodeURIComponent(readCookie('cookie_preferences')));
var pardotSubmitted = false;

// excute pardot JS if user consents to marketing cookies
if (cookiePreferences !== null) {
  if (cookiePreferences.marketing === true) {
    document.addEventListener("DOMContentLoaded", function (event) {

        /**
         * Send email address (and other data) to Pardot
         */
        $('form.pardot-submit').submit(function(event) {

            //removing gtm tag class so it won't fire twice
            $("form.pardot-submit button[type=submit]").removeClass("gtm--submit-aggregation-form");
            $("form.pardot-submit button[type=submit]").removeClass("gtm-form-submit");

            if (pardotSubmitted) {
                return true;
            }

            console.log('Sending to Pardot.');

            var email = $("form.pardot-submit input.pardot-email").val();
            
            var data = { "email": email };

            var otherFields = $("input.pardot-field");
            if (otherFields.length > 0) {
                otherFields.each(function(index, element) {
                    data[$(this).attr('name')] = $(this).val();
                });
            }

            if ($("input[name=subject]") != undefined) {
                var subject = $("input[name=subject]").val();

                data['subject'] = subject;
            }

            // remove subject hidden field
            $("input[name=subject]").remove();
 
            $.ajax({
                url: "/api/pardot-email",
                method: "POST",
                data: JSON.stringify(data),
                complete: function(){
                    pardotSubmitted = true;
                    console.log('Proceeding with submission.');
                }
            });
        });

    });

    }
}
