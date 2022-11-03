<?php

namespace SiteAudit;

use SiteAudit\SiteAuditTestTrait;

/**
 * Convenience class for creating fixtures.
 */
trait FixturesTrait
{
    use SiteAuditTestTrait;

    protected function fixtures()
    {
        return Fixtures::instance();
    }

}
