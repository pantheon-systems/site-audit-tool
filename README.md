Experimental Site Audit Tool
============================

[![Build Status](https://travis-ci.org/pantheon-systems/site-audit-tool.svg?branch=master)](https://travis-ci.org/pantheon-systems/site-audit-tool)

This is an experimental Drush extension.

Goal: Create a Global Drush command that runs all of the checks in the Site Audit 2.x branch.

Potential future goal: Share checks between the Global Drush command in the Site Audit 3.x branch.

See the [#3052993](https://www.drupal.org/project/site_audit/issues/3052993) in the Site Audit issue queue.

Usage
-----

In development, clone this repository, then set up the System Under Test (sut) via:
```
composer install
composer drupal:scaffold
```
That will set up your local project to run and test with Drush 9. To use Drush 8 instead:
```
composer scenario drush8
```
The [Composer Test Scenarios](https://github.com/g1a/composer-test-scenarios) project is used to manage the Composer dependencies needed to test different scenarios of this project. Running `composer scenario` is like running `composer install`; it will install the appropriate dependencies for the requested testing scenario. Run `composer install` to return to the default installation.

To use this extension as a global Drush command, set up your global drush.yml file as follows:


```
drush:
  paths:
    include:
      - '${env.home}/path/to/drush-extensions'
```

Then install this project to `~/path/to/drush-extensions/Commands/site-audit-tool`

Running Tests
-------------

Set up for 'development' as described in usage section. Then, run:
```
composer test
```

Ad-hoc Testing
--------------

In development:
```
composer drush audit:best-practices
```
