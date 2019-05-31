<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Database tests
 */
class DatabaseTest extends TestCase
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
    public function testDatabase()
    {
        // Run 'extensions' check on out test site
        $this->drush('audit:database');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('Every table is using UTF-8.', $json['checks']['SiteAuditCheckDatabaseCollation']['result']);
        $this->assertEquals('Every table is using InnoDB.', $json['checks']['SiteAuditCheckDatabaseEngine']['result']);
        $this->assertEquals('No tables with more than 1000 rows.', $json['checks']['SiteAuditCheckDatabaseRowCount']['result']);
    }

}
