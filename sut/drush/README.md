This directory contains commands, configuration and site aliases for the System Under Test (sut) for the Example Drush Extension project.

Configuration is available for both Drush 9 tests and Drush 8 tests.

## Drush 9
- drush/drush.yml
- drush/sites/self.site.yml

## Drush 8
- drush/drushrc.php
- drush/site-aliases/aliases.drushrc.php

With these configuration files in place, the tests for this project will run equivalenetly under either Drush 9 or Drush 8. In an actual Drupal site, there would usually only be configuration files for one Drush version.
