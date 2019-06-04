<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Block tests
 *
 * SiteAuditCheckBlockEnabled:
 *  - pass: drush pm:enable block
 *  - fail: drush pm:uninstall block
 */
class BlockTest extends TestCase
{
    // Run 'extensions' check on out test site
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
    public function testBlock()
    {
        //SiteAuditCheckBlockEnabled:
        //pass: drush pm:enable block
        $this->drush('pm:enable', ['block']);
        $this->drush('audit:block');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Block is enabled.', $json['checks']['SiteAuditCheckBlockEnabled']['result']);

        //fail: drush pm:uninstall block
        $this->drush('pm:uninstall', ['block']);
        $this->drush('audit:block');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Block is not enabled.', $json['checks']['SiteAuditCheckBlockEnabled']['result']);

        //reset
        $this->drush('pm:enable', ['block']);

    }

}
