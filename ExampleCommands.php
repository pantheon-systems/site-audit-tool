<?php

namespace Drush\Commands\example_drush_extension;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Consolidation\SiteProcess\ProcessManagerAwareInterface;
use Consolidation\SiteProcess\ProcessManagerAwareTrait;

/**
 * Edit this file to reflect your organization's needs.
 */
class ExampleCommands extends DrushCommands implements SiteAliasManagerAwareInterface, ProcessManagerAwareInterface
{
    use SiteAliasManagerAwareTrait;
    use ProcessManagerAwareTrait;

    /**
     * @command example:param
     *
     * Demonstrates a trivial command that takes a single required parameter.
     */
    public function exampleParam($param)
    {
        $this->io()->writeln('The parameter is ' . $param);
    }

    /**
     * @command example:input
     *
     * @param string $param
     * @option string $foo The "foo" option.
     * @default foo undefined
     *
     * Demonstrates a command that uses a Symfony Console InputInterface
     * to access the parameters and options passed to the command.
     */
    public function exampleInput(InputInterface $input)
    {
        $this->io()->writeln('The parameter is ' . $input->getArgument('param'));
        $this->io()->writeln('The "foo" option is ' . $input->getOption('foo'));
    }

    /**
     * @command example:log
     *
     * Demonstrates the use of notices, warnings and debug messages
     * in commands.
     */
    public function exampleLog()
    {
        $this->logger()->notice('This is a notice');
        $this->logger()->warning('This is a warning');
        $this->logger()->debug('This is a debug message');
    }

    /**
     * @command example:confirm
     *
     * Demonstrates how to prompt the user for confirmation and abort.
     */
    public function exampleConfirm()
    {
        $answer = $this->io()->confirm('Do you want to continue?');
        if (!$answer) {
            throw new UserAbortException("Command cancelled.");
        }
        $this->io()->writeln('Continuing...');
        sleep(3);
        $this->io()->writeln('Done.');
    }

    /**
     * @command example:alias
     *
     * Demonstrate how to use the alias manager to look up Drush aliases
     * based on the parameter passed on the commandline
     */
    public function exampleAlias($siteName)
    {
        $siteAlias = $this->siteAliasManager()->get($siteName);
        if ($siteAlias) {
            $this->io()->writeln('The site root is: ' . $siteAlias->root());
        }
    }

    /**
     * @command example:drush
     *
     * Demonstrate how to use the process manager to call a Drush
     * command. Use the alias manager to get a reference to @self
     * to use for this purpose.
     */
    public function exampleDrush()
    {
        $self = $this->siteAliasManager()->getSelf();
        $process = $this->processManager()->drush($self, 'core:status', [], ['format' => 'json']);

        $result = $process->mustRun();
        $data = $process->getOutputAsJson();
        $this->io()->writeln("The command result is:\n" . var_export($data, true));
    }

    /**
     * @command example:config
     *
     * Demonstrates how to access Drush configuration settings from a command.
     */
    public function exampleConfig($key)
    {
        $this->io()->writeln('The value is "' . $this->getConfig()->get($key) .'"');
    }

    /**
     * @command example:config:export
     * @return array
     *
     * Show the contents of the Drush configuration.
     */
    public function exampleConfigExport($options = ['format' => 'yaml'])
    {
        return $this->getConfig()->export();
    }
}
