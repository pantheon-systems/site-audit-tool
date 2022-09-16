Experimental Site Audit Tool
============================

[![Build Status](https://travis-ci.org/pantheon-systems/site-audit-tool.svg?branch=master)](https://travis-ci.org/pantheon-systems/site-audit-tool)
[![Actively Maintained](https://img.shields.io/badge/Pantheon-Actively_Maintained-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#actively-maintained-support)


This is an experimental Drush extension.

Goal: Create a Global Drush command that runs all of the checks in the Site Audit 2.x branch.

Potential future goal: Share checks between the Global Drush command in the Site Audit 3.x branch.

See the [#3052993](https://www.drupal.org/project/site_audit/issues/3052993) in the Site Audit issue queue.

Usage
-----

Ideally, this Drush command will become a dependency of the Site Audit module; if that happens, it will be available once the Site Audit module is installed into a Composer-managed site.

You may also install this module to any location that Drush searches for global or site-local commands. It does not need its vendor directory.

Development
-----------

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

Set up for 'development' as described in "Development" section. Then, run:
```
composer test
```

Ad-hoc Testing
--------------

In development:
```
composer drush audit:best-practices
```
This will run the given Drush command against a local test Drupal site, the "system under test". Run the tests once to install the Drupal site.


Testing via Tagged Deploy
-------------------------

* After your work is ready to test in a production environment, cut a new RC tag from your branch and push that tag to github.
* Check out the [cos-framework-clis repo](https://github.com/pantheon-systems/cos-framework-clis/)
* Update the `drush/Dockerfile` to use the tag you just pushed as the value for `site_audit_tool_version`
* At present, you must build the containers locally to push them to `quay.io`, so from the `cos-framework-clis` repo:
  * `cd drush`
  * `make build-drush10` (or whichever version needed)
  * `docker push {BUILD-TAG-NAME}`
    * Tag name should be something like `quay.io/getpantheon/cos-drush:v10-sandbox-eco-cef2a7a-dirty-689f4da`
    * If tag name is only a short name like `v10`, DO NOT PUSH OR YOU WILL DEPLOY IMMEDIATELY TO PRODUCTION.
* SSH into a Ygg node
  * `ygg /sites/{SITE}/environments/{ENVIRONMENT}/workflows -X POST -d '{"type": "change_environment_image", "params": {"server_type": "appserver", "container_name": "drush", "tag": "{TAG}"}}' | jq .`
* Your site will now be using the new site-audit-tool version and all related functionality can be tested.
