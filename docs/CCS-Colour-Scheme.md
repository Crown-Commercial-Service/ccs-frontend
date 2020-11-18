# Background

For all new services we should use the standard GOV.UK Design Patterns and Frontend Toolkit.

We should apply a minimal CCS-brand to this toolkit, replacing the ‘GOV.UK’ top bar with the CCS colour and brand, and replacing the footer where appropriate. All other design patterns should remain consistent with GOV.UK where possible.

* [Furthur Information](https://crowncommercialservice.atlassian.net/wiki/spaces/AG/pages/597164292/Page+Design)

## CCS Website Colour Scheme

The CCS website's CSS uses a custom colour scheme which is built on top of the imported GOV.UK frontend tool kit. 

The custom colour scheme used for CCS branding on the website is defined in a SASS settings file. This is located in 'assets/styles/_setting.scss'.

Custom variables are defined to override GOV.UK frontend styles which is explained below. 

```scss
// _settings.scss
$ccs-colour: #9B1A47;
$ccs-footer-colour: #2F2F2F;
$ccs-text-colour: #0B0C0C;

$ccs-button-colour: $blue-lagoon;
$ccs-button-hover-colour: #006770;

$ccs-border-colour: #E1E1E1;

$ccs-subtle-colour: #F6F6F6;
```

In the main styles.scss file ('assets/styles/styles.scss) the settings sass file is imported before importing all of the GOV.UK frontend styles and specifically before ‘@import "node_modules/govuk-frontend/all”. 

```scss
// _styles.scss
@import "settings";

// Import GOV.UK Frontend
@import "node_modules/govuk-frontend/all";

```

This ensures all custom variable settings are applied before and to override the gov.uk styles. 

The CCS brand colour scheme is mainly applied to the top bar and footer and various other components. All the rest is kept in line with GDS front-end kit (the style imported from gov.uk)

The gov.uk styles are imported into the node_modules folder. These are not changed. 
