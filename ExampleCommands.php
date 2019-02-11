<?php

namespace Drush\Commands\example_drush_extension;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;

/**
 * Edit this file to reflect your organization's needs.
 */
class ExampleCommands extends DrushCommands
{
    /**
     * @command example:param
     */
    public function exampleParam($param)
    {
        $this->io()->writeln('The parameter is ' . $param);
    }

    /**
     * @command example:log
     */
    public function exampleLog()
    {
        $this->logger()->notice('This is a notice');
        $this->logger()->warning('This is a warning');
        $this->logger()->debug('This is a debug message');
    }

    /**
     * @command example:config
     */
    public function exampleConfig($key)
    {
        $this->io()->writeln('The value is "' . $this->getConfig()->get($key) .'"');
    }
}
