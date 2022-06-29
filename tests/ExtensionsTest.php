<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Extensions tests
 *
 * SiteAuditCheckExtensionsCount:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckExtensionsDev:
 *  - pass: drush pm:uninstall field_ui views_ui
 *  - fail: drush pm:enable field_ui views_ui
 *
 * SiteAuditCheckExtensionsDuplicate:
 *  - pass: remove duplicate module
 *  - fail: copy a core module to contrib directory
 *
 * SiteAuditCheckExtensionsUnrecommended
 *  - pass: drush pm:uninstall php
 *  - fail: drush pm:enable php
 */
class ExtensionsTest extends TestCase
{
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

        $this->filesystem->remove('sut/web/modules/contrib/user');
        $this->filesystem->remove('sut/web/extension_duplicates');
    }

    /**
     * Test the SiteAuditCheckExtensionsDev check
     *
     * @throws \Exception
     */
    public function testExtensionsDev()
    {
        // SiteAuditCheckExtensionsDev
        // fail: drush pm:enable field_ui views_ui
        $this->drush('pm:enable', ['field_ui', 'views_ui']);

        // pass: drush pm:uninstall field_ui views_ui
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following development modules(s) are currently enabled: field_ui, views_ui', $json['checks']['SiteAuditCheckExtensionsDev']['result']);

        // fail: drush pm:enable field_ui views_ui
        $this->drush('pm:uninstall', ['field_ui', 'views_ui']);
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No enabled development extensions were detected; no action required.', $json['checks']['SiteAuditCheckExtensionsDev']['result']);

        // Re-enable field_ui and views_ui
        $this->drush('pm:enable', ['field_ui', 'views_ui']);
    }

    /**
     * Test the SiteAuditCheckExtensionsDuplicate check
     *
     * @throws \Exception
     */
    public function testExtensionsDuplicate()
    {
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckExtensionsDuplicate']['result'];
        $this->assertEquals('No duplicate extensions were detected.', $result);

        // Create a false duplicate.
        $extension_duplicates_path = 'sut/web/extension_duplicates';
        $this->filesystem->mkdir($extension_duplicates_path);
        $false_module_info_file = $extension_duplicates_path . '/user.info.yml';
        file_put_contents($false_module_info_file, 'not a valid *.info.yml file');
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckExtensionsDuplicate']['result'];
        $this->assertEquals('No duplicate extensions were detected.', $result);

        // fail: copy a core module to contrib directory
        $original_module = 'sut/web/core/modules/user';
        $duplicate_module = 'sut/web/modules/contrib/user';
        $this->filesystem->mirror($original_module, $duplicate_module);
        // Add missing core_version_requirement to avoid drush to crash.
        $contents = file_get_contents('sut/web/modules/contrib/user/user.info.yml');
        $contents .= "\ncore_version_requirement: ^8 || ^9";
        file_put_contents('sut/web/modules/contrib/user/user.info.yml', $contents);
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $result = $this->getOutputFromJSON()['checks']['SiteAuditCheckExtensionsDuplicate']['result'];
        $this->assertStringContainsString('The following duplicate extensions were found:', $result);
        $this->assertStringContainsString("user\n", $result);
        $this->assertStringContainsString('user.info.yml', $result);

        $this->assertStringContainsString('The following duplicate extensions were found:', $json['checks']['SiteAuditCheckExtensionsDuplicate']['result']);
        $this->assertStringContainsString('user', $json['checks']['SiteAuditCheckExtensionsDuplicate']['result']);

        // Don't leave the duplicate module around
        $fs->remove($duplicate_module);
    }

    /**
     * Test the SiteAuditCheckExtensionsUnrecommended check
     *
     * @throws \Exception
     */
    public function testExtensionsUnrecommended()
    {
        $this->drush('status', ['Drupal version'], ['format' => 'json']);
        $data = $this->getOutputFromJSON();
        $drupal_version = $data['drupal-version'];
        if ($drupal_version[0] != '8') {
            $this->markTestSkipped("php extension is not compatible with Drupal $drupal_version");
        }

        // SiteAuditCheckExtensionsUnrecommended
        // pass: drush pm:uninstall php
        $this->drush('pm:uninstall', ['php']);

        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No unrecommended extensions were detected; no action required.', $json['checks']['SiteAuditCheckExtensionsUnrecommended']['result']);

        // fail: drush pm:enable php
        $this->drush('pm:enable', ['php']);

        // Check to see if this makes the extensions SiteAuditCheckExtensionsUnrecommended check fail
        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();
        $this->assertEquals('The following unrecommended modules(s) currently exist in your codebase: php', $json['checks']['SiteAuditCheckExtensionsUnrecommended']['result']);

        // Don't leave php installed.
        $this->drush('pm:uninstall', ['php']);
    }

}
