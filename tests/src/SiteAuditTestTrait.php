<?php

namespace SiteAudit;

use Drush\TestTraits\DrushTestTrait;

trait SiteAuditTestTrait {

    use DrushTestTrait {
        DrushTestTrait::drush as parentDrush;
    }

    /**
     * {@inheritdoc}
     */
    public function drush($command, array $args = [], array $options = [], $site_specification = null, $cd = null, $expected_return = 0, $suffix = null, array $env = []) {
        $this->parentDrush($command, $args, $options, $site_specification, $cd, $expected_return, $suffix, $env);
        $outputRaw = $this->getOutputRaw();
        
        if (strpos($outputRaw, 'PHP Deprecated') !== FALSE) {
            throw new \Exception(sprintf("Command '%s' output must not contain PHP deprecation notices", $command));
        }

        $errorOutputRaw = $this->getErrorOutputRaw();

        if (strpos($errorOutputRaw, 'PHP Deprecated') !== FALSE) {
            throw new \Exception(sprintf("Command '%s' error output must not contain PHP deprecation notices", $command));
        }
    }
}