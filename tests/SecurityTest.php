<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Security tests.
 *
 * SiteAuditCheckSecurityMenuRouter:
 *  - n/a: This check would require modifying the SUT codebase
 */
class SecurityTest extends TestCase
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
     * @group security
     *
     * @throws \Exception
     */
    public function testSecurity()
    {
        // Run 'extensions' check on out test site
        $this->drush('audit:security');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('No known potentially malicious entries were detected in the menu_router table.', $json['checks']['SiteAuditCheckSecurityMenuRouter']['result']);
    }

}
