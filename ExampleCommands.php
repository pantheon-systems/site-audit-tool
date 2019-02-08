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
    public function example($param)
    {
        $this->io()->writeln('The parameter is ' . $param);
    }
}
