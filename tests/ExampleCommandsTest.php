<?php
namespace ExampleDrushExtension;

use PHPUnit\Framework\TestCase;
use TestUtils\DrushTestTrait;

/**
 * Some simple tests of our example extension, which does nothing more
 * than exercise Drush APIs.
 */
class ExampleCommandsTest extends TestCase
{
    use DrushTestTrait;

    /**
     * Test to see if an example command with a parameter can be called.
     * @covers ExampleCommands::exampleParam
     */
    public function testExampleParam()
    {
        $this->drush('example:param', ['test']);
        $this->assertOutputEquals('The parameter is test');
    }

    /**
     * Test to see if an example command with a Symfony InputInterface can be called.
     * @covers ExampleCommands::exampleInput
     */
    public function testExampleInputWithoutFooOption()
    {
        $this->drush('example:input', ['test']);
        $this->assertOutputEquals('The parameter is testand the "foo" option is bar');
    }

    /**
     * Test to see if an example command with a Symfony InputInterface can be
     * called with an option.
     * @covers ExampleCommands::exampleInput
     */
    public function testExampleInputWithFooOption()
    {
        $this->drush('example:input', ['test'], ['foo' => 'baz']);
        $this->assertOutputEquals('The parameter is testand the "foo" option is baz');
    }

    /**
     * Test Drush logging in verbose mode.
     * @covers ExampleCommands::exampleLog
     */
    public function testExampleLog()
    {
        $this->drush('example:log', ['-v']);
        $output = $this->getSimplifiedErrorOutput();
        $this->assertContains('This is a notice', $output);
        $this->assertContains('This is a warning', $output);
        $this->assertNotContains('This is a debug message', $output);
    }

    /**
     * Test Drush logging in debug mode.
     * @covers ExampleCommands::exampleLog
     */
    public function testExampleLogDebug()
    {
        $this->drush('example:log', ['-vvv']);
        $output = $this->getSimplifiedErrorOutput();
        $this->assertContains('This is a debug message', $output);
    }

    /**
     * Test accessing a site-local alias file via `drush sa`.
     * @covers ExampleCommands::exampleAlias
     */
    public function testExampleAlias()
    {
        $this->drush('example:alias', ['@other']);
        $this->assertOutputEquals('The site root is: /path/to/other/drupal');
    }

    /**
     * Test running `drush` through the process manager.
     * @covers ExampleCommands::exampleDrush
     */
    public function testExampleDrush()
    {
        $this->drush('example:drush');
        // Drush 8 will say 'drush.php', and Drush 9 will report 'drush'
        $output = $this->getSimplifiedOutput();
        $this->assertContains('The Drush script is drush', $output);
    }

    /**
     * Test direct config access
     * @covers ExampleCommands::exampleConfig
     */
    public function testExampleConfiguration()
    {
        // See sut/web/drush/drush.yml (Drush 9) and sut/web/drush/drushrc.php (Drush 8)
        $this->drush('example:config', ['example.key']);
        $this->assertOutputEquals('The value is "This is a configuration value"');
    }

    /**
     * Test config export
     * @covers ExampleCommands::exampleConfigExport
     */
    public function testExampleConfigurationExport()
    {
        $this->drush('example:config:export', [], ['format' => 'json']);
        $data = $this->getOutputFromJSON();
        $this->assertEquals('This is a configuration value', $data['example']['key']);
    }
}
