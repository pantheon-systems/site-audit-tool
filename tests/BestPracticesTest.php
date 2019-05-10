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
        $this->drush('site-install', [], ['db-url' => 'mysql://root@127.0.0.1/siteaudittooldb']);
    }

    /**
     * Test to see if an example command with a parameter can be called.
     * @covers ExampleCommands::exampleParam
     */
    public function testBestPractices()
    {
        $this->drush('audit:best-practices');
        $output = $this->getSimplifiedOutput();
        $this->assertContains('settings.php exists and is not a symbolic link', $output);
    }

}
