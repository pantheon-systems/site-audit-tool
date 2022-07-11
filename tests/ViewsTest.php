<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Views tests.
 *
 * SiteAuditCheckViewsCacheOutput:
 *  - n/a: Testing this check would require we modify views. Note also that
 *    default Drupal install does not pass current check.
 *
 * SiteAuditCheckViewsCacheResults:
 *  - n/a: Testing this check would require we modify views. Note also that
 *    default Drupal install does not pass current check.
 *
 * SiteAuditCheckViewsCount:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckViewsEnabled:
 *  - pass: drush pm:enable views
 *  - fail: drush pm:uninstall views
 */
class ViewsTest extends TestCase
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
     * @group views
     *
     * @throws \Exception
     */
    public function testViews()
    {
        // Run 'extensions' check on out test site
        $this->drush('pm:enable', ['views_ui']);
        $this->drush('audit:views');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following Views are not caching rendered output: watchdog', $json['checks']['SiteAuditCheckViewsCacheOutput']['result']);
        $this->assertEquals('The following Views are not caching query results: watchdog', $json['checks']['SiteAuditCheckViewsCacheResults']['result']);

        // Result is nondeterministic, so we test only the description to
        // simply confirm that the test at least ran.
        $this->assertEquals('Number of enabled Views.', $json['checks']['SiteAuditCheckViewsCount']['description']);

    }

    /**
     * @group views
     *
     * @throws \Exception
     */
    public function testViewsEnabled()
    {
        // SiteAuditCheckViewsEnabled:

        // pass: drush pm:enable views
        $this->drush('pm:enable', ['views']);
        $this->drush('audit:views');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Views is enabled.', $json['checks']['SiteAuditCheckViewsEnabled']['result']);

        // fail: drush pm:uninstall views
        $this->drush('pm:uninstall', ['views']);
        $this->drush('audit:views');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Views is not enabled.', $json['checks']['SiteAuditCheckViewsEnabled']['result']);

        // reset. Uninstalling 'views' also uninstalls 'views_ui'. We want to
        // leave the site in its default state after this test runs.
        $this->drush('pm:enable', ['views', 'views_ui']);
    }

}
