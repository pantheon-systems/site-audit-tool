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
