Example Drush Extension
=======================

[![Build Status](https://travis-ci.org/drush-ops/example-drush-extension.svg?branch=master)](https://travis-ci.org/drush-ops/example-drush-extension)

This is an example Drush extension that is compatible with both Drush 9.6+ and Drush 8.2+. It includes tests that run on both versions of Drush.

This this project demonstrates what is known as a "site-wide" Drush extension. Site-wide extensions are installed via Composer into a particular Drupal site. The other kinds of Drush extensions are Drush module commands and Drush global commands. See the [Creating Custom Drush Commands documentation](http://docs.drush.org/en/master/commands/) for more information.

For maximum compatibility with future versions of Drush, a site-wide Drush extension should only call the following APIs:

  - APIs provided by Drupal
  - APIs provided by Drupal's dependencies
  - Libraries decleared in the site-wide extension's own composer.json file
  
Avoid using Drush APIs, save for those that are defined by your command's base class, [DrushCommands](https://github.com/drush-ops/drush/blob/master/src/Commands/DrushCommands.php), or those that are provided by objects injected into your command class by Drush. See [ExampleCommands.php](ExampleCommands.php) for examples.

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

To use this extension in production (that is, to install it in a Drupal 8 site):
```
cd /path/to/my-drupal-composer-drupal-project
composer require drush/example-drush-extension
```

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
composer drush example:param test
```
In production:
```
cd /path/to/my-drupal-composer-drupal-project
drush example:param test
```
Customizing
-----------

1. Fork this repository.
2. Alter "name", "description" and etc. in composer.json to suit.
3. Rename ExampleCommands.php and ExampleCommandsTest.php for your project.
4. Configuration and site aliases for use in testing can be placed in 'sut/drush/drush.yml' and 'sut/drush/sites/self.site.yml', respectively.
5. Add your extension on packagist.org so that it may be installed via Composer.
