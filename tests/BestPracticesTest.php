<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Best Practices tests
 */
class BestPracticesTest extends TestCase
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
    public function testBestPractices()
    {
        // Run 'best-practices' check on our test site
        $this->drush('audit:best-practices');
        $json = $this->getOutputFromJSON();
        //$output = $this->getSimplifiedOutput(); 
        $this->assertEquals('Fast 404 pages are enabled.', $json['checks']['SiteAuditCheckBestPracticesFast404']['result']);
        $this->assertEquals('Neither modules/contrib nor modules/custom directories are present!', $json['checks']['SiteAuditCheckBestPracticesFolderStructure']['result']);
        $this->assertEquals('No multi-sites detected.', $json['checks']['SiteAuditCheckBestPracticesMultisite']['result']);
        $this->assertEquals('settings.php exists and is not a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesSettings']['result']);

        $this->assertEquals('services.yml does not exist! Copy the default.service.yml to services.yml and see https://www.drupal.org/documentation/install/settings-file for details.', $json['checks']['SiteAuditCheckBestPracticesServices']['result']);
        $this->assertEquals('sites.php does not exist.', $json['checks']['SiteAuditCheckBestPracticesSites']['result']);
        $this->assertEquals('Fast 404 pages are enabled.', $json['checks']['SiteAuditCheckBestPracticesSitesDefault']['result']);
        $this->assertEquals('No unnecessary files detected.', $json['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result']);

        // Disable Fast 404 configuration and check again
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 0]);
        $this->drush('audit:best-practices');
        $json = $this->getOutputFromJSON();
        $this->assertEquals(1, $json['checks']['SiteAuditCheckBestPracticesFast404']['score']);
        $output = $this->getSimplifiedOutput();
        $this->assertContains('Fast 404 pages are not enabled', $output);
        

        // Put Fast 404 configuration back
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 1]);
    }

}
