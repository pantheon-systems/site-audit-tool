<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Best Practices tests
 *
 * SiteAuditCheckBestPracticesFast404
 *  - pass: drush config-set system.performance fast_404.enabled 1
 *  - fail: drush config-set system.performance fast_404.enabled 0
 *
 * SiteAuditCheckBestPracticesFolderStructure:
 *  - pass: create 'contrib' and 'custom' directories in modules directory (in sut/web/modules)
 *  - fail: remove 'contrib' and 'custom' directories
 *
 * SiteAuditCheckBestPracticesMultisite:
 *  - pass: sut/web/sites does not contain any directories other than 'all' and 'default'
 *  - fail: sut/web/sites has other directories
 *
 * SiteAuditCheckBestPracticesSettings:
 *  - pass: sut/web/sites/default/settings.php exists
 *  - fail: Cannot test this, because removing settings.php makes Drush unusable
 *
 * SiteAuditCheckBestPracticesServices:
 *  - pass: sut/web/sites/default/services.yml exists
 *  - fail: sut/web/sites/default/services.yml
 *
 * SiteAuditCheckBestPracticesSites:
 *  - pass: Remove sut/web/sites/sites.php or make sure it is not a symlink
 *  - fail: sut/web/sites/sites.php is a symlink
 *
 * SiteAuditCheckBestPracticesSitesDefault:
 *  - pass: sut/web/sites/default exists and is not a symlink
 *  - fail: sut/web/sites/default does not exist
 *
 * SiteAuditCheckBestPracticesSitesSuperfluous:
 *  - pass: No extra files in sut/web/sites/default
 *  - fail: Create a file with an arbitrary name in sut/web/sites/default
 */
class BestPracticesTest extends TestCase
{
    // Run 'best-practices' check on our test site
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
    public function testBestPracticesFast404()
    {

        //SiteAuditCheckBestPracticesFast404

        //pass: drush config-set system.performance fast_404.enabled 1
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 1]);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Fast 404 pages are enabled.', $json['checks']['SiteAuditCheckBestPracticesFast404']['result']);

        //fail: drush config-set system.performance fast_404.enabled 0
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 0]);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Fast 404 pages are not enabled for any path.', $json['checks']['SiteAuditCheckBestPracticesFast404']['result']);
        //$output = $this->getSimplifiedOutput();
        //$this->assertContains('Fast 404 pages are not enabled', $output);

        //reset
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 1]);
    }

    public function testBestPracticesFolderStructure()
    {
        //SiteAuditCheckBestPracticesFolderStructure:

        //pass: create 'custom' directories in modules directory (in sut/web/modules)
        if (!file_exists('sut/web/modules/custom')) {
            mkdir('sut/web/modules/custom');
        }
    
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('modules/contrib and modules/custom directories exist.', $json['checks']['SiteAuditCheckBestPracticesFolderStructure']['result']);

        //fail: remove 'custom' directory
        rmdir('sut/web/modules/custom'); 
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('modules/custom directory is not present!', $json['checks']['SiteAuditCheckBestPracticesFolderStructure']['result']);

        //reset
        //no need to reset, default is no custom directory

    }

    public function testBestPracticesMultisite()
    {
        //SiteAuditCheckBestPracticesMultisite:

        //pass: sut/web/sites does not contain any directories other than 'all' and 'default'

        $multi = 'sut/web/sites/multi';
        if (file_exists($multi)) {
            rmdir($multi);
        }
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No multi-sites detected.', $json['checks']['SiteAuditCheckBestPracticesMultisite']['result']);

        //fail: sut/web/sites has other directories
        mkdir($multi);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Multisite directories are present but sites/sites.php is not present.', $json['checks']['SiteAuditCheckBestPracticesMultisite']['result']);

        //reset
        rmdir($multi);
        
    }

    public function testBestPracticesSettings()
    {
        //SiteAuditCheckBestPracticesSettings:

        //pass: sut/web/sites/default/settings.php exists
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('settings.php exists and is not a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesSettings']['result']);

        //fail: Cannot test this, because removing settings.php makes Drush unusable
        
    }

    public function testBestPracticesServices()
    {
        //SiteAuditCheckBestPracticesServices:

        //pass: sut/web/sites/default/services.yml exists
        //inconvenient to test, cannot create and open this file becuase of permission denied errors

        //fail: sut/web/sites/default/services.yml
        //unlink('sut/web/sites/default/services.yml');
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('services.yml does not exist! Copy the default.service.yml to services.yml and see https://www.drupal.org/documentation/install/settings-file for details.', $json['checks']['SiteAuditCheckBestPracticesServices']['result']);
        

        //reset
        //no need to reset, default is no file
    }

    public function testBestPracticesSites()
    {

        //SiteAuditCheckBestPracticesSites:

        //pass: Remove sut/web/sites/sites.php or make sure it is not a symlink
        if (file_exists('sut/web/sites/sites.php')) {
           unlink('sut/web/sites/sites.php');
        }

        if (file_exists('sut/web/sites/test.php')) {
           unlink('sut/web/sites/test.php');
        }
        
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('sites.php does not exist.', $json['checks']['SiteAuditCheckBestPracticesSites']['result']);

        //warn: sites.php exists but is not a symlink
        $site = 'sut/web/sites/sites.php';
        file_put_contents($site, "<?php \n //empty file");
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('sites.php is not a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesSites']['result']);
        unlink($site);

        //fail: sut/web/sites/sites.php is a symlink
        $target = 'sut/web/sites/test.php';
        file_put_contents($target, "<?php \n //empty file");
        $link_name = 'sut/web/sites/sites.php';
        //file_put_contents($link_name, "<?php \n //empty file");
        $test = symlink($target, $link_name);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('sites/sites.php is a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesSites']['result']);

        //reset: remove sites.php
        unlink($target);
        unlink($link_name);
        
    }

    public function testBestPracticesSitesDefault()
    {
        //SiteAuditCheckBestPracticesSitesDefault:

        //pass: sut/web/sites/default exists and is not a symlink
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('sites/default exists.', $json['checks']['SiteAuditCheckBestPracticesSitesDefault']['result']);

        //fail: sut/web/sites/default does not exist
        //can't test without removing everything in default

    }

    public function testBestPracticesSuperfluous()
    {
        //SiteAuditCheckBestPracticesSitesSuperfluous:

        //pass: No extra files in sut/web/sites/default
        if (file_exists('sut/web/sites/super.php')) {
            unlink('sut/web/sites/super.php');
        }

       
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No unnecessary files detected.', $json['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result']);

        //fail: Create a file with an arbitrary name in sut/web/sites/default
        $superfluous = 'sut/web/sites/super.php';
        //$handle = fopen($file, 'w');
        file_put_contents($superfluous, "<?php \n //empty file");
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following extra files were detected: super.php', $json['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result']);

        //reset
        unlink($superfluous);
        
    }

}
