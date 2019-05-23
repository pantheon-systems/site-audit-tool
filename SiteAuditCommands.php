<?php

namespace Drush\Commands\site_audit_tool;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\StructuredData\RowsOfFieldsWithMetadata;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use SiteAudit\SiteAuditCheckInterface;
use Symfony\Component\Console\Input\InputInterface;

// For testing
use SiteAudit\SiteAuditCheckBase;
use SiteAudit\Check\BestPracticesSettings;
use SiteAudit\Check\BestPracticesFast404;

/**
 * Edit this file to reflect your organization's needs.
 */
class SiteAuditCommands extends DrushCommands
{
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
     * @command audit:reports
     * @aliases aa
     * @return array
     *
     * @param string $param A parameter
     * @bootstrap full
     *
     * Combine all of our reports
     */
    public function auditReports(
        $param = '',
        $options = [
            'format' => 'json',

            // Ignore these legacy flags for now
            'html' => false,
            'json' => false,
            'detail' => false,
            'vendor' => '',
            'skip' => '',
        ])
    {
        $registry = new \stdClass();
        $checks = $this->interimInstantiateChecks($registry);
        $checks = $this->filterSkippedChecks($checks, $options['skip']);

        $result = $this->interimBuildReports($checks);

        // @todo Use output formatter. At the moment, the output formatter
        // insists on always pretty-printing with JSON_PRETTY_PRINT, but
        // the Pantheon dashboard expects non-pretty json, and does not
        // parse correctly with the extra whitespace.

        // return $result;

        print json_encode($result);
    }

    /**
     * @command audit:best-practices
     * @aliases audit-best-practices,abp
     * @field-labels
     *     label: Label
     *     description: Description
     *     result: Result
     *     action: Action
     *     score: Score
     * @return RowsOfFieldsWithMetadata
     *
     * @param string $param A parameter
     * @bootstrap full
     *
     * Demonstrates a trivial command that takes a single required parameter.
     */
    public function bestPractices(
        $param = '',
        $options = ['format' => 'json']
        )
    {
        $reportName = 'BestPractices';
        $registry = new \stdClass();
        $checks = $this->interimInstantiateChecks($registry);
        $reportChecks = $this->checksForReport($reportName, $checks);

        // Temporary code to be thrown away
        $report = $this->interimReport($this->interimReportLabel($reportName), $reportChecks);

        // Note that we could improve the table output with the annotation
        //   @default-fields description,result,action
        // This would also by default hide the remaining fields in json
        // format, though, which would not be desirable.
        // @todo: Add a separate 'default-fields' for human-readable output,
        // or maybe always ignore it in unstructured output modes.
        return (new RowsOfFieldsWithMetadata($report))
            ->setDataKey('checks');
    }

    protected function interimBuildReports($checks)
    {
        $reportsList = $this->interimReportsList();

        foreach ($reportsList as $report => $label) {
            $key = "SiteAuditReport$report";
            $reportChecks = $this->checksForReport($report, $checks);
            if (!empty($reportChecks)) {
                $reports[$key] = $this->interimReport($label, $reportChecks);
            }
        }

        return [
            'time' => time(),
            'reports' => $reports,
        ];
    }

    protected function interimReportsList()
    {
        return [
            'BestPractices' => "Best practices",
            'Cache' => "Drupal's caching settings",
            'Extensions' => "Extensions",
            'Cron' => "Cron",
            'Database' => "Database",
            'Users' => "Users",
            'FrontEnd' => "Front End",
            'Status' => "Status",
            'Watchdog' => "Watchdog database logs",
            'Views' => "Views",
        ];
    }

    protected function interimReportLabel($reportName)
    {
        $reports = $this->interimReportsList();

        return $reports[$reportName];
    }

    protected function interimInstantiateChecks($registry)
    {
        $checks = [
            new \SiteAudit\Check\BestPracticesSettings($registry),
            new \SiteAudit\Check\BestPracticesFast404($registry),
        ];

        return $checks;
    }

    protected function checksForReport($report, $checks)
    {
        $result = [];

        foreach ($checks as $check) {
            if (strpos(get_class($check), $report) !== false) {
                $result[] = $check;
            }
        }

        return $result;
    }

    protected function filterSkippedChecks($checks, $skipped)
    {
        // Pantheon by default skips:
        // insights,codebase,DatabaseSize,BlockCacheReport,DatabaseRowCount,content

        if (!is_array($skipped)) {
            $skipped = explode(',', $skipped);
        }

        foreach ($checks as $key => $check) {
            if (strpos(get_class($check), $check) !== false) {
                unset($checks[$key]);
            }
        }

        return $checks;
    }

    /**
     * Temporary code to be thrown away
     */
    protected function interimReport($label, $checks)
    {
        $score = 0;
        $max = 0;
        $checkResults = [];

        foreach ($checks as $check) {
            $max += 2;
            $score += $check->getScore();
            $checkResults += $this->interimReportResults($check);
        }

        $percent = ($score * 100) / $max;

        return [
            "percent" => (int) $percent,
            "label" => $label,
            "checks" => $checkResults,
        ];
    }

    /**
     * Temporary code to be thrown away
     */
    protected function interimReportResults(SiteAuditCheckInterface $check)
    {
        $checkName = $this->getCheckName($check);
        return [
            $checkName => [
                "label" => $check->getLabel(),
                "description" => $check->getDescription(),
                "result" => $check->getResult(),
                "action" => $check->renderAction(),
                "score" => $check->getScore(),
            ],
        ];
    }

    /**
     * Temporary code to be thrown away
     */
    protected function getCheckName(SiteAuditCheckInterface $check)
    {
        $full_class_name = get_class($check);
        return str_replace('\\', '', $full_class_name);
    }

}
