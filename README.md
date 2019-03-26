# Crown Commercial Service website

Symfony application to generate the Crown Commercial public website at [https://www.crowncommercial.gov.uk/](https://www.crowncommercial.gov.uk/)

Please see [further web documentation](https://github.com/Crown-Commercial-Service/ccsweb-docs/tree/master/web) (this is a private repo).

## Table of contents

- [Getting started](#getting-started)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Local dev](#local-dev)
- [Deployment](#deployment)
- [Continuous integration](#continuous-integration)
  - [PHP Unit](#php-unit)
  - [Behat](#behat)
  - [PHP CodeSniffer](#php-codesniffer)
- [Built with](#built-with)
- [Acknowledgments](#acknowledgments)

## Getting started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See [Deployment](#deployment) for notes on how to deploy the project on a live system.

### Requirements

* PHP 7.2+
* NPM 8.9.4

### Installation

A step-by-step guide to get a development environment running on your machine.

#### Composer

To install PHP packages for development, run:

```
composer install
```

To install PHP packages for Staging and Production, run:

```
composer install --no-dev --optimize-autoloader
```

#### Environment config

Add a file called `env.local` with the following configuration settings:

```
APP_API_BASE_URL='ENTER URL'
```

To explain these settings:

* APP_API_BASE_URL - Base URL to WordPress site suffixed with /wp-json/ (e.g. `https://cms.crowncommercial.local/wp-json/`)

#### NPM
Use NPM to compile Javascript modules and Sass into CSS. Install the long-term support (LTS) version of [Node.js](https://nodejs.org/en/), which includes NPM. The minimum version of Node required is 8.9.4. We recommend using [`nvm`](https://github.com/creationix/nvm) for managing versions of Node.

To install Node packages, run:

```
npm install
```

To create the folder structure required for `watch` to work, run (once):

```
npm run build
```

#### Building styles and scripts

We import the GOV.UK Frontend styles into the main Sass file in our project. All our own Sass variables are placed before `@import "node_modules/govuk-frontend/all";` to make sure the right settings have been set before we compile the Sass to CSS.

The JavaScript is copied from `node_modules/govuk-frontend/all.js` to `public/assets/scripts/all.js`, where it is referenced in all templates.

As the GOV.UK Frontend does not initialise any scripts by default; all scripts are initialised, using `initAll`, in `app.js`.

See [Gov.uk Design System's Coding standards](https://github.com/alphagov/govuk-frontend/tree/master/docs/contributing/coding-standards) to learn more about the standards we're following.

To watch for changes, run:

```
npm run watch
```

### Local dev

To view the website at http://127.0.0.1:8000, run:

```
bin/console server:run
```

Optionally: Set up local host http://local.crowncommercial.gov.uk/ to point to the `public/` folder.

#### Clear cache

```
bin/console cache:clear
```

## Deployment

To deploy to Development environment merge to `development` branch.

To deploy to PreProd environment merge to `preprod` branch. 

To deploy to Production environment open a Pull Request and merge to `master` branch.

## Continuous integration

We use [Travis CI](https://travis-ci.org/Crown-Commercial-Service/ccs-frontend) to run automated tests on all merges into development, preprod and master. 

### PHP Unit

Create unit tests in `tests/` and run via `bin/phpunit`

See [Getting Started with PHPUnit](https://phpunit.de/getting-started/phpunit-7.html)

### Behat

Create Behat tests in `features/`
 
To run first ensure you are running the local server via `bin/console server:run`

Run Behat tests via: `bin/behat` 

See [quick start](http://docs.behat.org/en/latest/quick_start.html) and [Behat and Mink](http://docs.behat.org/en/v2.5/cookbook/behat_and_mink.html)

### PHP CodeSniffer

You can test coding standards ([PSR2](https://www.php-fig.org/psr/psr-2/)) via:

```
# Summary report
vendor/bin/phpcs --report=summary

# Full details
vendor/bin/phpcs
```

Where possible you can auto-fix code via:

```
vendor/bin/phpcbf
```

## Built with

* [GOV.UK Design System](https://design-system.service.gov.uk/) - Frontend design system

## Acknowledgments

* [GDS](https://www.gov.uk/government/organisations/government-digital-service)
.
