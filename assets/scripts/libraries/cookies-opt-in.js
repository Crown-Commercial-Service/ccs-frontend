/**
 * Read cookie
 *
 * @param string name
 * @returns {*}
 * @see http://www.quirksmode.org/js/cookies.html
 */
window.readCookie = function (name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1, c.length);
        }
        if (c.indexOf(nameEQ) == 0) {
            return c.substring(nameEQ.length, c.length);
        }
    }
    return null;
};

(function () {

    // We'll use ES5 Template Literals to output these in a more maintainable way
    var initial_cookie_preferences = [
        {
            Title: "Usage",
            Description: "usage lorem blah blah.",
            CookieType: "usage",
            enabled: false,
            adjustable: true,
        },
        {
            Title: "Marketing",
            Description: "marketing lorem blah blah.",
            CookieType: "marketing",
            enabled: false,
            adjustable: true,
        },
        {
            Title: "Strictly necessary cookies",
            Description: "essen tial lorem blah blah.",
            CookieType: "essential",
            enabled: true,
            adjustable: false,
        }
    ];

    // Set the default
    var cookie_preferences = {
        essentials: true,
        usage: true,
        marketing: true,
    };

    /**
     * Set cookie
     *
     * @param string name
     * @param string value
     * @param int days
     * @param string path
     * @see http://www.quirksmode.org/js/cookies.html
     */
    function createCookie(name, value, days, path) {
        var expires = "";

        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toGMTString();
        }

        document.cookie = name + "=" + value + expires + "; path=" + path;
    }


    /**
     * Sets a cookie to hide the cookie message, and also removes it from the
     * current DOM view, and replaces it with a basic confirmation message
     */
    function hideMessage() {
        var cookieConsentContainer = document.getElementById('cookie-consent-container');
        if (cookieConsentContainer) {
            cookieConsentContainer.innerHTML = '<div class="cookie-message__inner site-container"><p>For more information, or to change your settings, please read <a href="/privacy-notice/">our privacy notice</a>.</p></div>';
            createCookie('dismiss-cookie-message', 'yes', 30, '/');
        }
    }


    /**
     * Opt the user in to certain cookies
     */
    function optUserIn() {
        hideMessage();
        // create a cookie that lets us detect the user has opted in
        createCookie('cookie-data-consent', 'yes', 30, '/');
    }


    /**
     * Opt the user in out of using certain cookies
     */
    function optUserOut() {
        hideMessage();

        // if the user previously opted-in, then delete the opt-in cookie
        var consentCookie = readCookie('cookie-data-consent');
        if (consentCookie !== null && consentCookie === 'yes') {
            // remove opt-in cookie
            createCookie('cookie-data-consent', '', -1, '/');
        }
    }


    function UpdateCookiePreferences() {

        // var cookie_preferences = [
        //     {
        //         CookieType: "usage",
        //         enabled: false,
        //     },
        //     {
        //         CookieType: "marketing",
        //         enabled: false,
        //     },
        //     {
        //         CookieType: "essential",
        //         enabled: true,
        //     }
        // ];

        // Update the defaults, based on selected radio options
        // cookie_preferences = JSON.parse(cookie_preferences);
        console.log({cookie_preferences});

        for (i=0; i <)

        cookie_preferences.keys(p).forEach(function (k) {
            cookieElement = document.getElementById(cookie_preferences[p]);
            cookie_preferences[p] = cookieElement.value();
        });

        createCookie('cookie_preferences', JSON.stringify(cookie_preferences), 365, '/');

        // set both of these to true when the user submits their preferences
        createCookie('seen_cookie_message', 'true', 365, '/');
        createCookie('cookie_preferences_set', 'true', 365, '/');

    }


    /**
     *
     * Used to generate a status message with a toggle button allowing the
     * user to change their opted in/out status
     *
     * @param appendTo
     * The HTML element to append this control to
     *
     * @param hasChanged
     * Whether the user has changed this value (we serve different text if that is the case)
     */
    function generateStatusMessage(appendTo, hasChanged) {
        hasChanged = typeof hasChanged !== 'undefined' ? hasChanged : false;

        // cookie that contains whether the user has opted-in/out
        var consentCookie = readCookie('cookie-data-consent');

        var messageContainer = document.createElement('div');
        messageContainer.classList.add('cookie-message-inline');

        var message = '';
        var buttonContainer = document.createElement('p');
        var toggleButton = document.createElement('button');
        toggleButton.classList.add('button');
        toggleButton.classList.add('button--tight');

        // conditional to serve a different message depending on if the user has
        // opted in or out
        if (consentCookie !== null && consentCookie === 'yes') {
            // if the user has just changed their opt-in/out setting, then
            // we server a different message (which says "you are now" to clarify
            // that the user has succesfully changed the setting
            if (hasChanged) {
                message = '<p>You are now opted-in to our advertising cookies.</p>';
            } else {
                message = '<p>You are currently opted-in to our advertising cookies.</p>';
            }

            toggleButton.innerHTML = 'Opt me out';
            toggleButton.addEventListener('click', function () {
                optUserOut();
                // we use recursion to re-generate this message once the setting
                // has been changed using `optUserOut()`
                generateStatusMessage(appendTo, true);
            });
        } else {
            // if the user has just changed their opt-in/out setting, then
            // we server a different message (which says "you are now" to clarify
            // that the user has succesfully changed the setting
            if (hasChanged) {
                message = '<p>You are now opted-out of our advertising cookies.</p>';
            } else {
                message = '<p>You are currently opted-out of our advertising cookies.</p>';
            }

            toggleButton.innerHTML = 'Opt me in';
            toggleButton.addEventListener('click', function () {
                optUserIn();
                // we use recursion to re-generate this message once the setting
                // has been changed using `optUserIn()`
                generateStatusMessage(appendTo, true);
            });
        }

        // build the message contents
        messageContainer.innerHTML = message;
        buttonContainer.appendChild(toggleButton);
        messageContainer.appendChild(buttonContainer);

        // clear the innerHTML of the container, incase we are regenerating
        // the message (in which case, there will be innacurate content in there)
        appendTo.innerHTML = '';
        appendTo.appendChild(messageContainer);
    }


    function generateCookieSettingsPageContent(appendTo) {

        var CookieSettingsPageContent = document.createDocumentFragment();
        var CookieSettingsSubmitButton = document.createDocumentFragment();
        var cookie_preferences = JSON.parse(readCookie('cookie_preferences'));

        console.log({cookie_preferences});

        // loop through the data
        initial_cookie_preferences.forEach((datarecord, idx) => {

            // If the cookie has already been set, match the key value to the
            if (cookie_preferences !== null) {
               datarecord.enabled = cookie_preferences[datarecord.CookieType];
            }

            // for each record we call out to a function to create the template
            let markup = createSeries(datarecord, idx);
            // We make a div to contain the resultant string
            let container = document.createElement("div");
            container.classList.add("govuk-form-group");
            // We make the contents of the container be the result of the function
            container.innerHTML = markup;
            // Append the created markup to the fragment
            CookieSettingsPageContent.appendChild(container);
        });



        function createSeries(datarecord, idx) {

            if (datarecord.adjustable === true) {

                return `
<fieldset class="govuk-fieldset" aria-describedby="changed-name-hint">
    <legend class="govuk-fieldset__legend govuk-fieldset__legend--xl">
      <h3 class="govuk-fieldset__heading">
        ${datarecord.Title}
      </h3>
    </legend>
    <span id="${datarecord.CookieType}-hint" class="govuk-hint">
      ${datarecord.Description}
    </span>
    <div class="govuk-radios govuk-radios--inline">
      <div class="govuk-radios__item">
                <input class="govuk-radios__input" id="${datarecord.CookieType}" name="${datarecord.CookieType}" type="radio"
                ${datarecord.enabled === true ? `checked` : ``}
                value="true">
                <label class="govuk-label govuk-radios__label" for="${datarecord.CookieType}">
          On
        </label>
      </div>
      <div class="govuk-radios__item">
        <input class="govuk-radios__input" id="${datarecord.CookieType}-2" name="${datarecord.CookieType}" type="radio"
        ${datarecord.enabled === true ? `` : `checked`}
                value="false">
                <label class="govuk-label govuk-radios__label" for="${datarecord.CookieType}-2">
          Off
        </label>
      </div>
    </div>
  </fieldset>
            `;

            }

            if (datarecord.adjustable === false) {

                return `
                <h3 class="">${datarecord.Title}</h3>
                <p>${datarecord.Description}</p>
                `;

            }


        }




        // var buttonContainer = document.createElement('p');
        var CookieSettingsSubmitButton = document.createElement('button');
        CookieSettingsSubmitButton.classList.add('button');
        CookieSettingsSubmitButton.innerHTML = 'Save changes';
        CookieSettingsSubmitButton.addEventListener('click', function () {
            UpdateCookiePreferences();
            // we use recursion to re-generate this message once the setting
            // has been changed using `optUserIn()`
            console.log("Your cookie settings were saved");
            // generateStatusMessage(appendTo, true);
        });


        // clear the innerHTML of the container, incase we are regenerating
        // the message (in which case, there will be innacurate content in there)
        appendTo.innerHTML = '';
        appendTo.appendChild(CookieSettingsPageContent);
        appendTo.appendChild(CookieSettingsSubmitButton);

    }


    /**
     * Programatically creates the cookie message notification
     */
    function createCookieMessage() {
        // create the cookie message container
        var cookieMessageContainer = document.createElement('div');
        cookieMessageContainer.classList.add('cookie-message');
        cookieMessageContainer.setAttribute('id', 'cookie-consent-container');
        cookieMessageContainer.setAttribute('aria-label', 'Cookie Policy');
        cookieMessageContainer.setAttribute('aria-live', 'polite');


        // create the inner contents of the cookie message
        var cookieMessageInner = document.createElement('div');
        cookieMessageInner.classList.add('cookie-message__inner', 'govuk-width-container');
        cookieMessageInner.classList.add('site-container');
        cookieMessageInner.innerHTML = '<div class="cookie-message__intro"><p>We use non-essential cookies to help us improve this website and our services. Any data collected is anonymised. By continuing to use this site, you agree to our use of cookies.</p></div>';

        var optInButton = document.createElement('button');
        optInButton.classList.add('button');
        optInButton.classList.add('button--tight');
        optInButton.classList.add('button--confirm');
        optInButton.innerHTML = "Accept cookies";
        optInButton.addEventListener('click', optUserIn);

        // var optOutButton = document.createElement('button');
        // optOutButton.classList.add('button');
        // optOutButton.classList.add('button--tight');
        // optOutButton.classList.add('button--deny');
        // optOutButton.innerHTML = "No";
        // optOutButton.addEventListener('click', optUserOut);

        var cookieMessageButtons = document.createElement('div');
        cookieMessageButtons.classList.add('cookie-message__actions');
        cookieMessageButtons.appendChild(optInButton);
        // cookieMessageButtons.appendChild(optOutButton);

        cookieMessageInner.appendChild(cookieMessageButtons);


        // append the inner contents to the cookie message container
        cookieMessageContainer.appendChild(cookieMessageInner);


        // add the cookie message to the start of the document body
        //document.body.prepend(cookieMessageContainer);
        var toContent = document.getElementById("skiplink-container");
        document.body.insertBefore(cookieMessageContainer, toContent);
        cookieMessageContainer.style.display = 'block';
    }


    /**
     * Only show the cookie message if the user hasn't previously dismissed it
     */
    var cookie = readCookie('dismiss-cookie-message');
    var consentCookie = readCookie('cookie-data-consent');
    if (cookie === null && cookie !== 'yes') {
        createCookieMessage();
    }


    /**
     * Run JavaScript for the component (component-cookie-consent) that displays
     * the current user opt-in/out status, with a toggle button
     */
    var cookieConsentAreas = document.getElementsByClassName('cookie-consent-information');
    for (var i = 0; i < cookieConsentAreas.length; i++) {
        // generateStatusMessage(cookieConsentAreas[i]);
        generateCookieSettingsPageContent(cookieConsentAreas[i]);
    }

})();
