<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Cron tests.
 *
 * SiteAuditCheckCronEnabled:
 *  - pass: drush config:set automated_cron.settings interval 10800
 *  - fail: drush config:set automated_cron.settings interval 0
 */
class CronTest extends TestCase
{
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
     *
     * @group cron
     *
     * @throws \Exception
     */
    public function testCron()
    {
        //SiteAuditCheckCronEnabled:
        //pass: drush config:set automated_cron.settings interval 10800
        $this->drush('config:set', ['automated_cron.settings', 'interval', 10800]);
        $this->drush('audit:cron');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Cron is set to run every 180 minutes.', $json['checks']['SiteAuditCheckCronEnabled']['result']);
        
        //fail: drush config:set automated_cron.settings interval 0
        $this->drush('config:set', ['automated_cron.settings', 'interval', 0]);
        $this->drush('cron');
        $this->drush('audit:cron');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Drupal Cron frequency is set to never, but has been executed within the past 24 hours (either manually or using drush cron).', $json['checks']['SiteAuditCheckCronEnabled']['result']);
    }

}
