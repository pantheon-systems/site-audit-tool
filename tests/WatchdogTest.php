<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Watchdog tests
 *
 * SiteAuditCheckWatchdog404:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckWatchdogAge:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckWatchdogCount:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckWatchdogEnabled:
 *  - pass: drush pm:enable dblog
 *  - warn: drush pm:uninstall dblog
 *
 * SiteAuditCheckWatchdogPhp:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckWatchdogSyslog:
 *  - warn: drush pm:uninstall syslog
 *  - fail: drush pm:enable syslog
 */
class WatchdogTest extends TestCase
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
     * @covers ExampleCommands::exampleParam
     */
    public function testWatchdog()
    {
        // Run 'watchdog' check on out test site
        $this->drush('audit:watchdog', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();

        // For the following tests, the result is nondeterministic, so we
        // check only the description to determine that the check at least ran.
        $this->assertEquals('No 404 entries.', $json['checks']['SiteAuditCheckWatchdog404']['result']);
        $this->assertEquals('Oldest and newest.', $json['checks']['SiteAuditCheckWatchdogAge']['description']);
        $this->assertEquals('Number of dblog entries.', $json['checks']['SiteAuditCheckWatchdogCount']['description']);
        $this->assertEquals('Count PHP notices, warnings and errors.', $json['checks']['SiteAuditCheckWatchdogPhp']['description']);

    }

    public function testWatchdogEnabled()
    {
        // SiteAuditCheckWatchdogEnabled:

        // pass: drush pm:enable dblog
        $this->drush('audit:watchdog', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Database logging (dblog) is enabled.', $json['checks']['SiteAuditCheckWatchdogEnabled']['result']);

        // warn: drush pm:uninstall dblog
        $this->drush('pm:uninstall', ['dblog']);
        $this->drush('audit:watchdog', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Database logging (dblog) is not enabled; if the site is having problems, consider enabling it for debugging.', $json['checks']['SiteAuditCheckWatchdogEnabled']['result']);

        // reset
        $this->drush('pm:enable', ['dblog']);
    }

    public function testWatchdogSyslog()
    {
        // SiteAuditCheckWatchdogSyslog

        // warn: drush pm:uninstall syslog
        $this->drush('pm:uninstall', ['syslog']);
        $this->drush('audit:watchdog', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Syslog logging is not enabled.', $json['checks']['SiteAuditCheckWatchdogSyslog']['result']);

        // fail: drush pm:enable syslog
        $this->drush('pm:enable', ['syslog']);
        $this->drush('audit:watchdog', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Syslog logging is enabled!', $json['checks']['SiteAuditCheckWatchdogSyslog']['result']);

        // reset
        $this->drush('pm:uninstall', ['syslog']);
    }

}
