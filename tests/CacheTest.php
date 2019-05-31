<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Best Practices tests
 */
class CacheTest extends TestCase
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
    public function testCache()
    {
        // Run 'cache' check on out test site
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Expiration of cached pages not set!', $json['checks']['SiteAuditCheckCachePageExpire']['result']);
        $this->assertEquals('CSS aggregation and compression is enabled.', $json['checks']['SiteAuditCheckCachePreprocessCSS']['result']);
        $this->assertEquals('JavaScript aggregation is enabled.', $json['checks']['SiteAuditCheckCachePreprocessJS']['result']);

        /*
        // Set the page cache expiration
        $this->drush('TODO');

        // Check to see if the cache expiration check is now passing
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();

        // Clear the page cache expiration again
        $this->drush('TODO');
        */
    }

}
