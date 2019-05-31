<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Views tests
 */
class ViewsTest extends TestCase
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
    public function testViews()
    {
        // Run 'extensions' check on out test site
        $this->drush('audit:views');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following Views are not caching rendered output: watchdog', $json['checks']['SiteAuditCheckViewsCacheOutput']['result']);
        $this->assertEquals('The following Views are not caching query results: watchdog', $json['checks']['SiteAuditCheckViewsCacheResults']['result']);
        $this->assertEquals('There are 12 enabled views.', $json['checks']['SiteAuditCheckViewsCount']['result']);
        $this->assertEquals('Views is enabled.', $json['checks']['SiteAuditCheckViewsEnabled']['result']);
    }

}
