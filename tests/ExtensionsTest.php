<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

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

    public function setUp(): void
    {
        $this->fixtures()->createSut();
    }

    public function tearDown(): void
    {
        $this->fixtures()->tearDown();
    }

    /**
     * Test the SiteAuditCheckExtensionsDev check
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
     */
    public function testExtensionsDuplicate()
    {
        $fs = new Filesystem();
        $original_module = 'sut/web/core/modules/user';
        $duplicate_module = 'sut/web/modules/contrib/user';

        // pass: remove duplicate module
        $fs->remove($duplicate_module);

        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();

        $this->assertEquals('No duplicate extensions were detected.', $json['checks']['SiteAuditCheckExtensionsDuplicate']['result']);

        // fail: copy a core module to contrib directory
        $fs->mirror($original_module, $duplicate_module);

        $this->drush('audit:extensions', [], ['vendor' => 'pantheon']);
        $json = $this->getOutputFromJSON();

        $this->assertContains('The following duplicate extensions were found:', $json['checks']['SiteAuditCheckExtensionsDuplicate']['result']);
        $this->assertContains('user', $json['checks']['SiteAuditCheckExtensionsDuplicate']['result']);

        // Don't leave the duplicate module around
        $fs->remove($duplicate_module);
    }

    /**
     * Test the SiteAuditCheckExtensionsUnrecommended check
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
