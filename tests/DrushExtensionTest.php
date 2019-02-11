<?php
namespace ExampleDrushExtension;

use PHPUnit\Framework\TestCase;
use TestUtils\DrushTestTrait;

/**
 * Some simple tests of our example extension, which does nothing more
 * than exercise Drush APIs.
 */
class DrushExtensionTest extends TestCase
{
    use DrushTestTrait;

    /**
     * Test to see if an example command with a parameter can be called.
     */
    public function testExampleParam()
    {
        $this->drush('example:param', ['foo']);
        $this->assertOutputEquals('The parameter is foo');
    }

    /**
     * Test Drush logging.
     */
    public function testExampleLog()
    {
        $this->drush('example:log');
        $output = $this->getSimplifiedErrorOutput();
        $this->assertContains('This is a notice', $output);
        $this->assertContains('This is a warning', $output);
        $this->assertNotContains('This is a debug message', $output);

        $this->drush('example:log', ['-vvv']);
        $output = $this->getSimplifiedErrorOutput();
        $this->assertContains('This is a debug message', $output);
    }

    /**
     * Test direct config access
     */
    public function testExampleConfiguration()
    {
        $this->drush('example:config', ['example.key']);
        $this->assertOutputEquals('The value is "This is a configuration value"');
    }
}
