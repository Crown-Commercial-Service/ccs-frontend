# Pardot integration

[Pardot](https://www.salesforce.com/products/pardot/overview/) is a marketing automation / tracking tool to help 
Crown Commercial Service make better decision on how to improve their website.

The CCS website has basic integration with Pardot, detailed below.

By default visitors in Pardot are [anonymous](https://help.salesforce.com/articleView?id=pardot_visitors.htm&type=5). 
To convert a visitor to a "prospect" we need to associate them with an email. This is done via:

* Submits a Pardot form that is in an iframe on your web page
* Submits a form on your site that’s connected to a Pardot form handler

## Setup

Add a [Form Handler](https://pi.pardot.com/formHandler) to Pardot to accept incoming email addresses from form 
submissions. You need to enable a mode on the form called "Kiosk/Data Entry mode" to support data being sent from the 
server.   

Add the Form Handler submission URL to your `.env.local` file in the `PARDOT_FORM_URL` variable.

Example `.env.local` file (replace the URL with the actual Form Handler submission URL).

```
# Pardot form URL to submit email address to
PARDOT_EMAIL_FORM_HANDLER_URL='https://go.pardot.com/l/XXX'
```

Please note Form Handlers provide a robust and flexible way to integrate forms with Pardot, though do not support the 
Pardot cookie when data is sent from the server (in Kiosk mode) which may reduce tracking / reporting. 

### Website tracking 

In Pardot tracking code is setup on a [campaign](https://pi.pardot.com/campaign). To get the tracking code view the 
campaign and select _View Tracking code_.

Tracking code has been added to the base template (`templates/base.html.twig`) and is added just before the closing 
body tag.

### Submitting user's email address from a form

A range of Salesforce Web-to-Case forms exist on the CCS website. To submit the email address to Pardot without full 
server-side processing of forms we use a small AJAX JavaScript to submit the email address to Pardot.

Include this on the page via including the following JS in your Twig template:

```
{% block nonblocking_javascript %}
    {{ parent() }}
    <script src="/assets/scripts/pardot.js"></script>
{% endblock %}
```

Next, you need to assign a custom class name to the form tag and email input field. 

* Form tag: `pardot-submit`
* Email input field: `pardot-email`
* Any other fields you want to submit: `pardot-field`

For example:

```
<form class="pardot-submit" ... >
   <input type="email" name="email" class="pardot-email" ... >
   <input type="text" name="company" class="pardot-field" ... >
```

On form submission the JS will submit the email address, and any other fields, to the Pardot form handler.

Please note other form fields you submit to Pardot must exist in Pardot with the same name (in the example above the 
web form field _company_ submits to the Pardot form handler _company_ field). 

## Pardot cookies

These are detailed here https://help.salesforce.com/articleView?id=pardot_basics_cookies.htm&type=5

At present we have seen this cookie with the use of form handlers:

* Cookie: `visitor_id<accountid>`
* Purpose: Track user in Pardot
* Lifetime: 10 years

