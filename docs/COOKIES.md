# Cookies

Cookies are managed through vanilla JavaScript and a small third-party framwork which gives us date range and full unicode support.

## Requirements

- NPM v.8.9.4

Run `npm install` to install package manager.

Run `npm run build` to build the assets, which includes the JavaScript file.

## Behaviour

If JavaScript is available and the user hasn't already seen the cookie banner, show it to them.

If the user visits the cookie settings page and JavaScript is available, then show grouped consent options. Don't show the cookie banner on this page.

## Default cookies

By default, only essential cookies are enabled. Essential cookies are defined as cookies which the site relies on to provide basic functionality, such as remembering that the user has already seen and dismissed the cookie message.

Default settings: `{"essential":true,"settings":false,"usage":false,"glassbox":false}`

Update the `cookie_preferences` and `cookie_preferences_accepted` objects to change which cookies are enabled by default and by user action.

```javascript
var cookie_preferences = {
    essentials: true,
    usage: false,
    marketing: false,
    glassbox: false
};

var cookie_preferences_accepted = {
    essentials: true,
    usage: true,
    marketing: true,
    glassbox: true,
};
```

## Cookies list

Update the `initial_cookie_preferences` object to match the actual cookies being used on the site. They are listed here because there is a function which deletes all set cookies when their permissions are revoked. And to do this we need to specify the cookies: `name`, `path`, and `domain`.

```javascript
[
        {
            title: "Cookies that measure website use",
            description: `<p>We use Google Analytics to measure how you use the website so we can improve it based on user needs. Google Analytics sets cookies that store anonymised information about:</p>
                          <ul>
                            <li>how you got to the site</li>
                            <li>the pages you visit on Crown Commercial Service (CCS), and how long you spend on each page</li>
                            <li>what you click on while you're visiting the site</li>
                          </ul>`,
            cookie_type: "usage",
            enabled: null,
            adjustable: true,
            cookies: [
                {
                    "name": "1P_JAR",
                    "path": "/",
                    "domain": ".google.com"
                }
            ]
        },

        {
            title: "Measuring website usage (Glassbox)",
            description: `<p>We use Glassbox software to collect information about how you use CCS. We do this to help make sure the site is meeting the needs of its users and to help us make improvements</p>
                          <p>Glassbox stores information about:</p>
                          <ul>
                            <li>Browsing activity</li>
                            <li>Click-stream activity</li>
                            <li>Session heatmaps and</li>
                            <li>Scrolls</li>
                          </ul>
                          <p>This information can’t be used to identify who you are.</p>
                          <p>We don’t allow Glassbox to use or share our analytics data.</p>`,
            cookie_type: "glassbox",
            enabled: null,
            adjustable: true,
            cookies: [
                {
                    "name": "_cls_s",
                    "path": "/",
                    "domain": ".crowncommercial.gov.uk"
                },
                {
                    "name": "_cls_v",
                    "path": "/",
                    "domain": ".crowncommercial.gov.uk"
                },
            ]
        },

        {
            title: "Cookies that help with our communications and marketing",
            description: "These cookies may be set by third party websites and do things like measure how you view YouTube videos that are on Crown Commercial Service (CCS).",
            cookie_type: "marketing",
            enabled: null,
            adjustable: true,
            cookies: null,
        },
        {
            title: "Strictly necessary cookies",
            description: "<p>These essential cookies do things like:</p><ul><li>remember the notifications you've seen so we do not show them to you again</li><li>remember your progress through a form (for example a licence application)</li></ul><p>They always need to be on.</p>",
            cookie_type: "essential",
            enabled: null,
            adjustable: false,
            cookies: null,
        }
    ];
```

`adjustable: false` means the cookie group can't be changed by the end-user.

## Including the JavaScript file on a page

The JavaScript file is used in the `base.html.twig` template.

`<script src="/assets/scripts/libraries/cookies-opt-in.js?v=2.0.0"></script>`
