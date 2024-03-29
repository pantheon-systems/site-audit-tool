<?php

namespace SiteAudit;

use Symfony\Component\Filesystem\Filesystem;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

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
 *  - fail: sut/web/sites/default/services.yml does not exist
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

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    protected function set_up()
    {
        $this->fixtures()->createSut();
        $this->filesystem = new Filesystem();
    }

    protected function tear_down()
    {
        $this->fixtures()->tearDown();

        if ($this->filesystem->exists('sut/web/modules_backup')) {
          $this->filesystem->remove('sut/web/modules');
          $this->filesystem->rename('sut/web/modules_backup', 'sut/web/modules');
        }

        $this->filesystem->remove(
          array(
            'sut/web/modules/custom',
            'sut/web/sites/multi',
            'sut/web/sites/test.php',
            'sut/web/sites/super.php',
            'sut/web/sites/sites.php',
            'sut/web/sites/.gitignore',
          )
        );
    }

    /**
     * Test to see if an example command with a parameter can be called.
     *
     * @group best_practices
     *
     * @throws \Exception
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

        //reset
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 1]);
    }

    /**
     * @group best_practices
     *
     * @throws \Exception
     */
    public function testBestPracticesFolderStructure()
    {
        $this->filesystem = new Filesystem();
        $this->filesystem->rename('sut/web/modules', 'sut/web/modules_backup');
        $this->filesystem->remove('sut/web/modules');

        // No "/modules" directory.
        $this->assertFolderStructure('Contrib and custom modules not found.');

        // Empty "/modules" directory.
        $this->filesystem->mkdir('sut/web/modules');
        $this->assertFolderStructure('Contrib and custom modules not found.');

        // Only a single invalid subdirectory.
        $this->assertFolderStructure(
            'Either modules/contrib or modules/custom directories are not present!',
            array('invalid_subdir')
        );

        // Only a single valid subdirectory: "contrib".
        $this->assertFolderStructure(
            'modules/contrib directory exist.',
            array('contrib')
        );

        // Only a single valid subdirectory: "composer".
        $this->assertFolderStructure(
            'modules/composer directory exist.',
            array('composer')
        );

        // Only a single valid subdirectory: "custom".
        $this->assertFolderStructure(
            'modules/custom directory exist.',
            array('custom')
        );

        // An invalid subdirectory and a valid one.
        $this->assertFolderStructure(
            'modules/contrib directory is not present!',
            array('invalid_subdir', 'custom')
        );
        $this->assertFolderStructure(
            'modules/custom directory is not present!',
            array('invalid_subdir', 'contrib')
        );
        $this->assertFolderStructure(
            'modules/custom directory is not present!',
            array('invalid_subdir', 'composer')
        );

        // Two invalid subdirectories.
        $this->assertFolderStructure(
            'Neither modules/contrib nor modules/custom directories are present!',
            array('invalid_subdir1', 'invalid_subdir2')
        );

        // Valid "contrib" and "custom" subdirectories.
        $this->assertFolderStructure(
            'modules/contrib and modules/custom directories exist.',
            array('contrib', 'custom')
        );

        // Valid "composer" and "custom" subdirectories.
        $this->assertFolderStructure(
            'modules/composer and modules/custom directories exist.',
            array('composer', 'custom')
        );
    }

    /**
     * Asserts "SiteAuditCheckBestPracticesFolderStructure" result.
     *
     * @param string $expectedOutput
     * @param array $subDirsToCreate
     *
     * @throws \Exception
     */
    private function assertFolderStructure($expectedOutput, $subDirsToCreate = array())
    {
        foreach ($subDirsToCreate as $subDir) {
            $this->filesystem->mkdir('sut/web/modules/' . $subDir);
        }

        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals($expectedOutput, $json['checks']['SiteAuditCheckBestPracticesFolderStructure']['result']);

        foreach ($subDirsToCreate as $subDir) {
            $this->filesystem->remove('sut/web/modules/' . $subDir);
        }
    }

    /**
     * @group best_practices
     *
     * @throws \Exception
     */
    public function testBestPracticesMultisite()
    {
        //SiteAuditCheckBestPracticesMultisite:

        //pass: sut/web/sites does not contain any directories other than 'all' and 'default'
        $multi_dir = 'sut/web/sites/multi';
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No multi-sites detected.', $json['checks']['SiteAuditCheckBestPracticesMultisite']['result']);

        //fail: sut/web/sites has other directories
        $this->filesystem->mkdir($multi_dir);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Multisite directories are present but sites/sites.php is not present.', $json['checks']['SiteAuditCheckBestPracticesMultisite']['result']);
    }

    /**
     * @group best_practices
     *
     * @throws \Exception
     */
    public function testBestPracticesSettings()
    {
        //SiteAuditCheckBestPracticesSettings:

        //pass: sut/web/sites/default/settings.php exists
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('settings.php exists and is not a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesSettings']['result']);

        //fail: Cannot test this, because removing settings.php makes Drush unusable
    }

    /**
     * @group best_practices
     *
     * @throws \Exception
     */
    public function testBestPracticesServices()
    {
        //SiteAuditCheckBestPracticesServices:

        //pass: sut/web/sites/default/services.yml exists
        $services_path = 'sut/web/sites/default/services.yml';
        $contents = <<< __EOT__
parameters:
  http.response.debug_cacheability_headers: true
__EOT__;
        chmod(dirname($services_path), 0777);
        file_put_contents($services_path, $contents);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('services.yml exists and is not a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesServices']['result']);

        //fail: sut/web/sites/default/services.yml
        $this->filesystem->remove($services_path);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('services.yml does not exist! Copy the default.service.yml to services.yml and see https://www.drupal.org/documentation/install/settings-file for details.', $json['checks']['SiteAuditCheckBestPracticesServices']['result']);
    }

    /**
     * @group best_practices
     *
     * @throws \Exception
     */
    public function testBestPracticesSites()
    {
        //SiteAuditCheckBestPracticesSites:

        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('sites.php does not exist.', $json['checks']['SiteAuditCheckBestPracticesSites']['result']);

        //warn: sites.php exists but is not a symlink
        $sites_php_file = 'sut/web/sites/sites.php';
        file_put_contents($sites_php_file, "<?php \n //empty file");
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('sites.php is not a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesSites']['result']);
        $this->filesystem->remove($sites_php_file);

        //fail: sut/web/sites/sites.php is a symlink
        $test_php_file = 'sut/web/sites/test.php';
        file_put_contents($test_php_file, "<?php \n //empty file");
        $link_name = 'sut/web/sites/sites.php';
        symlink($test_php_file, $link_name);
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('sites/sites.php is a symbolic link.', $json['checks']['SiteAuditCheckBestPracticesSites']['result']);
    }

    /**
     * @group best_practices
     *
     * @throws \Exception
     */
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

    /**
     * @group best_practices
     *
     * @throws \Exception
     */
    public function testBestPracticesSuperfluous()
    {
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result'];
        $this->assertEquals('No unnecessary files detected.', $result);

        $superfluous = 'sut/web/sites/super.php';
        file_put_contents($superfluous, "<?php \n //empty file");
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result'];
        $this->assertEquals('The following extra files were detected: super.php', $result);
        $this->filesystem->remove($superfluous);

        $sites_php_file = 'sut/web/sites/sites.php';
        file_put_contents($sites_php_file, "<?php \n //empty file");
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result'];
        $this->assertEquals('The following extra files were detected: sites.php', $result);
        $this->drush('audit:best-practices');
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result'];
        $this->assertEquals('No unnecessary files detected.', $result);
        $this->filesystem->remove($sites_php_file);

        $gitignore_file = 'sut/web/sites/.gitignore';
        file_put_contents($gitignore_file, '# A test .gitignore file');
        $this->drush('audit:best-practices', [], ['vendor' => 'pantheon']);
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckBestPracticesSitesSuperfluous']['result'];
        $this->assertEquals('No unnecessary files detected.', $result);
    }

}
