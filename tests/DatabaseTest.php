<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Database tests.
 */
class DatabaseTest extends TestCase
{
    use FixturesTrait;

    protected function set_up()
    {
        $this->fixtures()->createSut();
    }

    protected function tear_down()
    {
        $this->fixtures()->tearDown();
    }

    /**
     * Test to see if an example command with a parameter can be called.
     *
     * @group database
     *
     * @throws \Exception
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
