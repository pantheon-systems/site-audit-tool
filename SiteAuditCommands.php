<?php

namespace Drush\Commands\site_audit_tool;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;

// For testing
use SiteAudit\SiteAuditCheckBase;
use SiteAudit\Check\BestPracticesSettings;

/**
 * Edit this file to reflect your organization's needs.
 */
class SiteAuditCommands extends DrushCommands implements SiteAliasManagerAwareInterface
{
    use SiteAliasManagerAwareTrait;

    /**
     * @hook init
     *
     * Autoload our files if they are not already loaded.
     * Drush should do this as a service for global commands based
     * off of the information in composer.json. At the moment,
     * though, it does not.
     *
     * n.b. this hook runs when any command in this file is executed.
     */
    public function init()
    {
        if (!class_exists(SiteAuditCheckBase::class)) {
            $loader = new \Composer\Autoload\ClassLoader();
            $loader->addPsr4('SiteAudit\\', __DIR__ . '/src');
            $loader->register();
        }
    }

    /**
     * @command audit:best-practices
     * @aliases audit-best-practices,abp
     *
     * @param string $param A parameter
     * @bootstrap full
     *
     * Demonstrates a trivial command that takes a single required parameter.
     */
    public function bestPractices($param)
    {
        $this->io()->writeln('The parameter is ' . $param);

        $check = new BestPracticesSettings();

        $this->io()->writeln('The description of ' . $check->getId() . ' is ' . $check->getDescription());

    }

}
