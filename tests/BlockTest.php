<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Best Practices tests
 */
class BlockTest extends TestCase
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
    public function testBlock()
    {
        // Run 'extensions' check on out test site
        $this->drush('audit:block');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Block is enabled.', $json['checks']['SiteAuditCheckBlockEnabled']['result']);
    }

}
