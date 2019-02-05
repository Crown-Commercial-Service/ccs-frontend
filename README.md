# Crown Commercial Service website

Symfony application to generate the Crown Commercial public website at https://www.crowncommercial.gov.uk/

### Requirements

* PHP 7.2+
* NPM 8.9.4

## Deployment

To deploy to Development environment merge to `development` branch.

To deploy to PreProd environment merge to `preprod` branch. 

To deploy to Production environment open a Pull Request and merge to `master` branch.

## Continuous integration

@todo

### PHP Unit

Create unit tests in `tests/` and run via `bin/phpunit`

See [Getting Started with PHPUnit](https://phpunit.de/getting-started/phpunit-7.html)

### Behat

Create Behat tests in `features/`
 
To run first ensure you are running the local server via `bin/console server:run`

Run Behat tests via: `bin/behat` 

See [quick start](http://docs.behat.org/en/latest/quick_start.html) and [Behat and Mink](http://docs.behat.org/en/v2.5/cookbook/behat_and_mink.html)

## Installation

### Composer

Run Composer to install PHP packages:

```
composer install
```

On Staging and Production use this to install PHP packages:

```
composer install --no-dev --optimize-autoloader
```

### Node package manager (NPM)

Run NPM to install package dependencies for Javascript. We use this to compile Javascript modules and Sass into CSS.

```
# We use Node version manager (NVM) to change the active version of node
nvm use 8.11.1
npm install

# Run the first time you install the package dependencies
npm run build
```

```
# To watch for changes
npm run watch
```

### Local dev

Run this command to view the website at http://127.0.0.1:8000

```
bin/console server:run
```

Or setup local host http://local.crowncommercial.gov.uk/ to point to the `public/` folder.

