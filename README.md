# Crown Commercial Service website

Symfony application to generate the Crown Commercial public website at [https://www.crowncommercial.gov.uk/](https://www.crowncommercial.gov.uk/)

Please see [further web documentation](https://github.com/Crown-Commercial-Service/ccsweb-docs/tree/master/web) (this is a private repo).

## Table of contents

- [Getting started](#getting-started)
- [Deployment](#deployment)
- [Continuous integration](#continuous-integration)
- [Built with](#built-with)
- [Acknowledgments](#acknowledgments)

## Additional documentation

- [Cookies](/docs/COOKIES.md)

## Getting started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See [Deployment](#deployment) for notes on how to deploy the project on a live system.

### Requirements

* PHP 7.3+
* NPM 8.9.4
* Elasticsearch 7.x+

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

#### Elasticsearch

[Installation instructions for Elasticsearch](https://www.elastic.co/downloads/elasticsearch).

Run `bin/elasticsearch` (from Elasticsearch root directory) to make Frameworks and Suppliers search work.

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

The JavaScript is copied from `node_modules/govuk-frontend/all.js` to `public/assets/scripts/all.js` (and `public/assets/scripts/all.min.js`), where it is referenced in all templates.

As the GOV.UK Frontend does not initialise any scripts by default; all scripts are initialised, using `initAll`, in `app.js`.

See [Gov.uk Design System's Coding standards](https://github.com/alphagov/govuk-frontend/tree/master/docs/contributing/coding-standards) to learn more about the standards we're following.

To watch for changes, run:

```
npm run watch
```

### Local dev

Set up local host http://local.crowncommercial.gov.uk/ to point to the `public/` folder.

Optionally, you can view the website at http://127.0.0.1:8000, by running run:

```
bin/console server:run
```

Although we wouldn't recommend using this method long-term as the environment is less customisable and reliable.

#### Clear cache

To clear the Symfony cache (e.g. compiled templates) run:

```
bin/console cache:clear
```

To clear the application cache (e.g. cached API data) run:

```
bin/console cache:pool:clear cache.app_clearer
```

To clear the HTTP cache run:

```
rm -Rf var/cache/prod/http_cache
```

For more information about the caching setup for the site please read [CACHING.md](CACHING.md).

## Deployment

### Testing changes

1. Test in a feature branch.
2. Merge to `development` branch to test in Development environment.
3. Merge to `preprod` branch to test in PreProd (UAT) environment.
4. Get client to test and approve change.

### Deploy a change to Production

1. Create Pull Request to merge changes into `master`, ensure you add details of tickets you are fixing in the PR.
2. Code must pass automatic tests & be approved by one other person.
3. Email internal-it@crowncommercial.gov.uk to ask approval of this PR.
4. Once approved, merge into master. This deploys to Production. 

For more information on deployment please [read the relevant docs](https://github.com/Crown-Commercial-Service/ccsweb-docs/blob/master/web/DEPLOYMENT.md) (private repo).

See details on [Environments](https://github.com/Crown-Commercial-Service/ccsweb-docs/blob/master/web/ENVIRONMENTS.md) (private docs).

### Production checks

There are a few deployment checks that are required to pass before merging new code into production, notably:

* Code must pass static code analysis tests & automated tests (Travis).
* Manual review by CCS TechOps to approve Pull Request.

## Continuous integration

We use [Travis CI](https://travis-ci.org/Crown-Commercial-Service/ccs-frontend) to run automated tests on all merges into development, preprod and master. 

### PHP CodeSniffer

Application code must meet the ([PSR12](https://www.php-fig.org/psr/psr-12/)) coding standard. 
We currently ignore long line lengths, though we can fix this in the future if desired. PHPCS configuration can be found 
in `phpcs.xml.dist`.
 
You can test for this via:

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

### PHP Unit

Create unit tests in `tests/` and run via `bin/phpunit`

See [Getting Started with PHPUnit](https://phpunit.de/getting-started/phpunit-7.html)

### Behat

_Please note_: Behat is not currently used in CI but has a basic setup.

Create Behat tests in `features/`
 
To run first ensure you are running the local server via `bin/console server:run`

Run Behat tests via: `bin/behat` 

See [quick start](http://docs.behat.org/en/latest/quick_start.html) and [Behat and Mink](http://docs.behat.org/en/v2.5/cookbook/behat_and_mink.html).


## Built with

* [GOV.UK Design System](https://design-system.service.gov.uk/) - Frontend design system

## Acknowledgments

* [GDS](https://www.gov.uk/government/organisations/government-digital-service)
.
