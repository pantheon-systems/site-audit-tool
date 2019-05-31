<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Cron tests
 */
class CronTest extends TestCase
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
    public function testCron()
    {
        // Run 'extensions' check on out test site
        $this->drush('config:set', ['automated_cron.settings', 'interval', 10800]);
        $this->drush('audit:cron');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Cron is set to run every 180 minutes.', $json['checks']['SiteAuditCheckCronEnabled']['result']);
        $this->drush('config:set', ['automated_cron.settings', 'interval', 0]);
        $this->drush('audit:cron');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Drupal Cron frequency is set to never, but has been executed within the past 24 hours (either manually or using drush cron).', $json['checks']['SiteAuditCheckCronEnabled']['result']);
    }

}
