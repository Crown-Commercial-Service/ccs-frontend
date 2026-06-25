#!/bin/bash

# Print commands as they execute
set -x

# Install dependencies (from before_install section)
composer self-update
composer install

export APP_ENV=test
rm -rf var/cache/test/*
mkdir -p var/cache/test var/log
chmod -R 777 var/cache var/log

# Run tests (from script section)
vendor/bin/phpunit
vendor/bin/phpunit vendor/ccs/strata-frontend/tests