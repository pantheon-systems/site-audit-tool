<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Cache tests
 *
 * SiteAuditCheckCacheBinsAll:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckCacheBinsDefault:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckCacheBinsUsed:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckCachePageExpire:
 *  - pass: drush config:set system.performance cache.page.max_age 1000
 *  - fail: drush config:set system.performance cache.page.max_age 0
 *
 * SiteAuditCheckCachePreprocessCSS:
 *  - pass: composer drush config:set system.performance css.preprocess 1
 *  - fail: composer drush config:set system.performance css.preprocess 0
 *
 * SiteAuditCheckCachePreprocessJS:
 *  - pass: composer drush config:set system.performance js.preprocess 1
 *  - fail: composer drush config:set system.performance js.preprocess 0
 */
class CacheTest extends TestCase
{
    // Run 'cache' check on out test site

    use FixturesTrait;

    protected function set_up()
    {
        $this->fixtures()->createSut();
    }

    protected function tear_down()
    {
        $this->fixtures()->tearDown();
    }

    /**
     * Test to see if an example command with a parameter can be called.
     * @covers ExampleCommands::exampleParam
     */
    public function testCachePageExpire()
    {
        
        //SiteAuditCheckCachePageExpire:
        
        //default: no expiration set
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Expiration of cached pages not set!', $json['checks']['SiteAuditCheckCachePageExpire']['result']);
        
        //pass: drush config:set system.performance cache.page.max_age 1000
        $this->drush('config:set', ['system.performance', 'cache.page.max_age', 1000]);
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Expiration of cached pages is set to 17 min.', $json['checks']['SiteAuditCheckCachePageExpire']['result']);

        //fail: drush config:set system.performance cache.page.max_age 0
        $this->drush('config:set', ['system.performance', 'cache.page.max_age', 100]);
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Expiration of cached pages only set to 2 min.', $json['checks']['SiteAuditCheckCachePageExpire']['result']);

        // reset
        $this->drush('config:set', ['system.performance', 'cache.page.max_age', 100]);
    }

    public function testCachePreprocessCSS()
    {
        //SiteAuditCheckCachePreprocessCSS:
        //pass: composer drush config:set system.performance css.preprocess 1
        $this->drush('config:set', ['system.performance', 'css.preprocess', 1]);
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('CSS aggregation and compression is enabled.', $json['checks']['SiteAuditCheckCachePreprocessCSS']['result']);

        //fail: composer drush config:set system.performance css.preprocess 0
        $this->drush('config:set', ['system.performance', 'css.preprocess', 0]);
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('CSS aggregation and compression is not enabled!', $json['checks']['SiteAuditCheckCachePreprocessCSS']['result']);

        //reset
        $this->drush('config:set', ['system.performance', 'css.preprocess', 1]);
        
    }

    public function testCachePreprocessJS()
    {
        //SiteAuditCheckCachePreprocessCSS:
        //pass: composer drush config:set system.performance css.preprocess 1
        $this->drush('config:set', ['system.performance', 'js.preprocess', 1]);
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('JavaScript aggregation is enabled.', $json['checks']['SiteAuditCheckCachePreprocessJS']['result']);

        //fail: composer drush config:set system.performance css.preprocess 0
        $this->drush('config:set', ['system.performance', 'js.preprocess', 0]);
        $this->drush('audit:cache');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('JavaScript aggregation is not enabled!', $json['checks']['SiteAuditCheckCachePreprocessJS']['result']);

        //reset
        $this->drush('config:set', ['system.performance', 'js.preprocess', 1]);
    }

}
