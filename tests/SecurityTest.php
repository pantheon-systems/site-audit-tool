<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Security tests
 *
 * SiteAuditCheckSecurityMenuRouter:
 *  - n/a: This check would require modifying the SUT codebase
 */
class SecurityTest extends TestCase
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
    public function testSecurity()
    {
        // Run 'extensions' check on out test site
        $this->drush('audit:security');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No known potentially malicious entries were detected in the menu_router table.', $json['checks']['SiteAuditCheckSecurityMenuRouter']['result']);
    }

}
