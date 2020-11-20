<?php
namespace SiteAudit;

use PHPUnit\Framework\TestCase;

/**
 * Users tests
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

    public function testUsersBlockedNumberOne()
    {
        //SiteAuditCheckUsersBlockedNumberOne:
        //pass: drush user:block admin
        $this->drush('user:block', ['admin']);
        $this->drush('audit:users');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('UID #1 is blocked.', $json['checks']['SiteAuditCheckUsersBlockedNumberOne']['result']);

        //fail: drush user:unblock admin
        $this->drush('user:unblock', ['admin']);
        $this->drush('audit:users');
        $json = $this->getOutputFromJSON();
        $this->assertEquals('UID #1 is not blocked!', $json['checks']['SiteAuditCheckUsersBlockedNumberOne']['result']);

        //reset
        $this->drush('user:block', ['admin']);
    }
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
