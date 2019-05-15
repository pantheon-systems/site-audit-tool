<?php
namespace Drush\Commands\site_audit_tool;

use PHPUnit\Framework\TestCase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Best Practices tests
 */
class BestPracticesTest extends TestCase
{
    use DrushTestTrait;

    public function setUp()
    {
        // @todo: skip install if already installed.
        // @todo: pull db credentials from phpunit.xml configuration

        // Make settings.php writable again
        chmod('sut/web/sites/default/', 0755);
        @unlink('sut/web/sites/default/settings.php');
        copy('sut/web/sites/default/default.settings.php', 'sut/web/sites/default/settings.php');

        // Run site-install (Drupal makes settings.php unwritable)
        $this->drush('site-install', [], ['db-url' => 'mysql://root@127.0.0.1/siteaudittooldb']);
    }

    /**
     * Test to see if an example command with a parameter can be called.
     * @covers ExampleCommands::exampleParam
     */
    public function testBestPractices()
    {
        // Run 'best-practices' check on a fresh site
        $this->drush('audit:best-practices');
        $output = $this->getSimplifiedOutput();
        $this->assertContains('settings.php exists and is not a symbolic link', $output);
        $this->assertContains('Fast 404 pages are enabled', $output);

        // Disable Fast 404 configuration and check again
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 0]);
        $this->drush('audit:best-practices');
        $output = $this->getSimplifiedOutput();
        $this->assertContains('Fast 404 pages are not enabled', $output);

        // Put Fast 404 configuration back
        $this->drush('config:set', ['system.performance', 'fast_404.enabled', 1]);
    }

}
