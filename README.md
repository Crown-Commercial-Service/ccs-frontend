# Crown Commercial Service website

Symfony application to generate the Crown Commercial public website at https://www.crowncommercial.gov.uk/

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

Create Behat tests in `features/` and run via `bin/behat` 

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

### Local dev

Run this command to view the website at http://127.0.0.1:8000

```
bin/console server:run
```

Or setup local host http://local.crowncommercial.gov.uk/ to point to the `public/` folder.

### Requirements

* PHP 7.2+

