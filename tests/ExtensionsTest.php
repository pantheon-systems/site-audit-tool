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
 *  - n/a: This check would require modifying the SUT codebase
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
        // Run 'extensions' check on out test site
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following development modules(s) are currently enabled: field_ui, views_ui', $json['checks']['SiteAuditCheckExtensionsDev']['result']);

        // Disable the development modules
        $this->drush('pm:uninstall', ['field_ui', 'views_ui']);

        // Check to see if the 'extensions' dev modules check is now passing
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No enabled development extensions were detected; no action required.', $json['checks']['SiteAuditCheckExtensionsDev']['result']);

        // Re-enable the development modules
        $this->drush('pm:enable', ['field_ui', 'views_ui']);
    }

}
