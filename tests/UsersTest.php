<?php
namespace SiteAudit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Users tests.
 *
 * SiteAuditCheckUsersBlockedNumberOne:
 *  - pass: drush user:block admin
 *  - fail: drush user:unblock admin
 *
 * SiteAuditCheckUsersCountAll:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckUsersCountBlocked:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckUsersRolesList:
 *  - n/a: This check is informational only, and never fails
 *
 * SiteAuditCheckUsersWhoIsNumberOne:
 *  - n/a: This check is informational only, and never fails
 */
class UsersTest extends TestCase
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
     * @group users
     *
     * @throws \Exception
     */
    public function testUsersBlockedNumberOne()
    {
        //SiteAuditCheckUsersBlockedNumberOne:
        //pass: drush user:block admin
        $this->drush('user:block', ['admin']);
        $this->drush('audit:users');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('UID #1 is blocked, as recommended.', $json['checks']['SiteAuditCheckUsersBlockedNumberOne']['result']);

        //fail: drush user:unblock admin
        $this->drush('user:unblock', ['admin']);
        $this->drush('audit:users');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('UID #1 should be blocked, but is not.', $json['checks']['SiteAuditCheckUsersBlockedNumberOne']['result']);

        //reset
        $this->drush('user:block', ['admin']);
    }

    /**
     * @group users
     *
     * @throws \Exception
     */
    public function testUsers()
    {
        // Run 'users' check on out test site
        $this->drush('audit:users');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('There is one user.', $json['checks']['SiteAuditCheckUsersCountAll']['result']);
        $this->assertEquals('There is one blocked user.', $json['checks']['SiteAuditCheckUsersCountBlocked']['result']);
        $this->assertEquals('administrator: 1', $json['checks']['SiteAuditCheckUsersRolesList']['result']);
        $this->assertEquals('UID #1: admin, email: admin@example.com', $json['checks']['SiteAuditCheckUsersWhoIsNumberOne']['result']);
    }

}
