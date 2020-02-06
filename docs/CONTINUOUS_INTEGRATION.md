# Continuous integration

The frontend web projects runs through Travis CI, at present the backend WordPress does not but the intention is to set this up. This document summarisies the Continuous Integration (CI) setup of these projects.

## Frontend

* [Travis CI: ccs-frontend](https://travis-ci.org/Crown-Commercial-Service/ccs-frontend)
* [Travis config](https://github.com/Crown-Commercial-Service/ccs-frontend/blob/master/.travis.yml)

The following CI logic is currently used:

* Runs on all commits to development, preprod and master branches
* If CI tests fail you cannot merge into the branch (e.g. make changes live)

The following tests are run:

* Runs as PHP 7.2 and 7.3
* Runs PHP code linting on 7.2 to ensure code syntax is valid
* Runs PHP Unit (unit testing), this currently tests:
    * Core frontend library
	* No other frontend functionality (i.e. no end to end testing)

We recommend the following additional CI setup:

* Run tests on only PHP 7.3, switch syntax test to PHP 7.3
* Checks for any security advisories on third-party PHP code loaded via Composer (this is done on development locally at present)
* Create unit tests for new search feature
* Create unit tests for import feature
* Review whether we can use Behat tests (end-to-end or integration tests). This needs feasibility testing since difficult to automatically test anything that depends on a database (since tests run in Travis)

## WordPress (backend)

* [Travis CI: ccs-wordpress](https://travis-ci.org/Crown-Commercial-Service/ccs-wordpress) (not being used at present)

We recommend a similar CI setup to above with the addition of:

* Run WordPress through [WPScan Vulnerability Database](https://wpvulndb.com/) to check for any security advisories on WordPress copre or plugin code