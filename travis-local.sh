#!/bin/bash

# Print commands as they execute
set -x

# Install dependencies (from before_install section)
composer self-update
composer install

# Run tests (from script section)
bin/phpunit
bin/phpunit vendor/ccs/strata-frontend/tests