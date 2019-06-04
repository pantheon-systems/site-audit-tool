<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Views tests
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
        $this->drush('pm:enable', ['views_ui']);
        $this->drush('audit:views');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following Views are not caching rendered output: watchdog', $json['checks']['SiteAuditCheckViewsCacheOutput']['result']);
        $this->assertEquals('The following Views are not caching query results: watchdog', $json['checks']['SiteAuditCheckViewsCacheResults']['result']);
        // Answer is variable, so testing description instead
        $this->assertEquals('Number of enabled Views.', $json['checks']['SiteAuditCheckViewsCount']['description']);
        
    }

    public function testViewsEnabled()
    {
        //SiteAuditCheckViewsEnabled:
        //pass: drush pm:enable views
        //$this->drush('pm:enable', ['views_ui']);
        $this->drush('pm:enable', ['views']);
        $this->drush('audit:views');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Views is enabled.', $json['checks']['SiteAuditCheckViewsEnabled']['result']);

        //fail: drush pm:uninstall views
        $this->drush('pm:uninstall', ['views']);
        $this->drush('audit:views');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Views is not enabled.', $json['checks']['SiteAuditCheckViewsEnabled']['result']);

        //reset
        //$this->drush('pm:enable', ['views_ui']);
        $this->drush('pm:enable', ['views']);
    }

}
