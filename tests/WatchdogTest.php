<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Watchdog tests
 */
class WatchdogTest extends TestCase
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
    public function testWatchdog()
    {
        // Run 'extensions' check on out test site
        $this->drush('audit:watchdog');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No 404 entries.', $json['checks']['SiteAuditCheckWatchdog404']['result']);
        $this->assertEquals('There are 39 log entries.', $json['checks']['SiteAuditCheckWatchdogCount']['result']);
        $this->assertEquals('Database logging (dblog) is enabled.', $json['checks']['SiteAuditCheckWatchdogEnabled']['result']);
        $this->assertEquals('notice: 1, info: 1 - total 5.13%', $json['checks']['SiteAuditCheckWatchdogPhp']['result']);
        $this->assertEquals('Syslog logging is not enabled.', $json['checks']['SiteAuditCheckWatchdogSyslog']['result']);
    }

}
