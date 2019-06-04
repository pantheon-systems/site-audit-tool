<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Extensions tests
 *
 * SiteAuditCheckExtensionsCount:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckExtensionsDev:
 *  - pass: drush pm:uninstall field_ui views_ui
 *  - fail: drush pm:enable field_ui views_ui
 *
 * SiteAuditCheckExtensionsDuplicate:
 *  - n/a: This check would require modifying the SUT codebase
 *
 * SiteAuditCheckExtensionsUnrecommended
 *  - pass: drush pm:uninstall memcache
 *  - fail: drush pm:enable memcache
 */
class ExtensionsTest extends TestCase
{
    use FixturesTrait;

    public function setUp()
    {
        $this->fixtures()->createSut();
    }

    public function tearDown()
    {
        $this->fixtures()->tearDown();
    }

    /**
     * Test to see if an example command with a parameter can be called.
     * @covers ExampleCommands::exampleParam
     */
    public function testExtensions()
    {
        // Enable the development modules and disable memcache
        $this->drush('pm:enable', ['field_ui', 'views_ui']);
        $this->drush('pm:uninstall', ['memcache']);

        // Run 'extensions' check on out test site
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following development modules(s) are currently enabled: field_ui, views_ui', $json['checks']['SiteAuditCheckExtensionsDev']['result']);
        $this->assertEquals('No unrecommended extensions were detected; no action required.', $json['checks']['SiteAuditCheckExtensionsUnrecommended']['result']);

        // Disable the development modules
        $this->drush('pm:uninstall', ['field_ui', 'views_ui']);

        // Check to see if the 'extensions' dev modules check is now passing
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No enabled development extensions were detected; no action required.', $json['checks']['SiteAuditCheckExtensionsDev']['result']);

        // Enable the memcache module
        $this->drush('pm:enable', ['memcache']);

        // Check to see if this makes the extensions SiteAuditCheckExtensionsUnrecommended check fail
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following unrecommended modules(s) currently exist in your codebase: memcache', $json['checks']['SiteAuditCheckExtensionsUnrecommended']['result']);

        // Don't leave memcache installed. Turn back on field_ui and views_ui
        $this->drush('pm:uninstall', ['memcache']);
        $this->drush('pm:enable', ['field_ui', 'views_ui']);
    }

}
