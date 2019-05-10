<?php

// Add our commandfile into the cached commandfile context. Only do this for testing.
$drush_extension_namespace = '\\Drush\\Commands\\site_audit_tool\\SiteAuditCommands';
$drush_extension_filepath = dirname(dirname(__DIR__)) . '/SiteAuditCommands.php';
$annotation_commandfiles = drush_get_context('DRUSH_ANNOTATED_COMMANDFILES');
$annotation_commandfiles[$drush_extension_filepath] = $drush_extension_namespace;
drush_set_context('DRUSH_ANNOTATED_COMMANDFILES', $annotation_commandfiles);

// Used in testExampleConfiguration in tests/DrushExtensionTest.php
$options['example.key'] = 'This is a configuration value';
