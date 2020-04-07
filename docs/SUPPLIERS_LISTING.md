# Suppliers Listing

The Suppliers Listing page (https://www.crowncommercial.gov.uk/suppliers) has been written as a JavaScript 
web app so that it can update the results without re-loading the whole page.

This has been accomplished using the [Vue JS JavaScript Framework](https://vuejs.org/).

The relevant code for this can be found in the following template file:

`templates/suppliers/list.html.twig`

Some notes about this code (and potential future improvements that could be made):

 - The non-minified version of Vue JS is being used. This is due to the minified version surfacing fatal errors 
 which prevent the page from working.
 - It would be beneficial to use Babel, or some more advanced feature detection within the code (some ES6 functionality is used).
 
 ## Non-JavaScript fallback
 
 Please note that this page must with with and without JavaScript. That means that the page must work identically (wherever possible) if 
 the user has JavaScript disabled.
 
 Some of the code in the template may look unusual; but it has likely been written in this way to facilitate this goal.
 
 Please bear this requirement in mind for all future development work.
