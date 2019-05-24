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
use SiteAudit\ChecksRegistry;

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
        $checks = $this->interimInstantiateChecks($this->createRegistry($options));
        $checks = $this->filterSkippedChecks($checks, $options['skip']);

        $result = $this->interimBuildReports($checks);

        // Hack: avoid using the output format when `--json` is specified.
        // At the moment, the output formatter
        // insists on always pretty-printing with JSON_PRETTY_PRINT, but
        // the Pantheon dashboard expects non-pretty json, and does not
        // parse correctly with the extra whitespace.
        if ($options['json']) {
            print json_encode($result);
            return null;
        }

        // Otherwise, use the output formatter
        return $result;
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
     * @bootstrap full
     *
     * Demonstrates a trivial command that takes a single required parameter.
     */
    public function auditBestPractices(
        $options = ['format' => 'json']
        )
    {
        return $this->singleReport('best_practices', $options);
    }

    /**
     * @command audit:extensions
     * @aliases audit_extensions,ae
     * @field-labels
     *     label: Label
     *     description: Description
     *     result: Result
     *     action: Action
     *     score: Score
     * @return RowsOfFieldsWithMetadata
     *
     * @bootstrap full
     *
     * Audit extensions (modules and themes).
     */
    public function auditExtensions(
        $options = ['format' => 'json']
        )
    {
        return $this->singleReport('extensions', $options);
    }

    protected function singleReport($reportId, $options)
    {
        $checks = $this->interimInstantiateChecks($this->createRegistry($options));
        $reportChecks = $this->checksForReport($reportId, $checks);

        // Temporary code to be thrown away
        $report = $this->interimReport($this->interimReportLabel($reportId), $reportChecks);

        // Note that we could improve the table output with the annotation
        //   @default-fields description,result,action
        // This would also by default hide the remaining fields in json
        // format, though, which would not be desirable.
        // @todo: Add a separate 'default-fields' for human-readable output,
        // or maybe always ignore it in unstructured output modes.
        return (new RowsOfFieldsWithMetadata($report))
            ->setDataKey('checks');
    }

    protected function createRegistry($options = [])
    {
        $options += [
            'vendor' => '',
            'html' => false,
            'detail' => false,
        ];

        $registry = new \stdClass();

        // We'd rather 'registry' be a class with an interface, but
        // since we do not have that, we will simply add these options
        // as attributes of the stdClass to serve as a replacement for
        // drush_get_option().
        $registry->vendor = $options['vendor'];
        $registry->html = $options['html'];
        $registry->detail = $options['detail'];

        $registry->checksList = new ChecksRegistry();

        return $registry;
    }

    protected function interimBuildReports($checks)
    {
        $reportsList = $this->interimReportsList();

        foreach ($reportsList as $reportId => $label) {
            $key = $this->interimReportKey($reportId);
            $reportChecks = $this->checksForReport($reportId, $checks);
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
            'best_practices' => "Best practices",
            'cache' => "Drupal's caching settings",
            'extensions' => "Extensions",
            'cron' => "Cron",
            'database' => "Database",
            'users' => "Users",
            'front_end' => "Front End",
            'status' => "Status",
            'watchdog' => "Watchdog database logs",
            'views' => "Views",
        ];
    }

    protected function interimReportLabel($reportId)
    {
        $reports = $this->interimReportsList();

        return $reports[$reportId];
    }

    protected function interimReportKey($reportId)
    {
        // Convert from snake_case to CamelCase and append to SiteAuditReport
        return 'SiteAuditReport' . str_replace(' ', '', ucwords(str_replace('_', ' ', $reportId)));
    }

    protected function interimInstantiateChecks($registry)
    {
        $checks = [

            // best_practices
            new \SiteAudit\Check\BestPracticesFast404($registry),
            new \SiteAudit\Check\BestPracticesFolderStructure($registry),
            new \SiteAudit\Check\BestPracticesMultisite($registry),
            new \SiteAudit\Check\BestPracticesSettings($registry),
            new \SiteAudit\Check\BestPracticesServices($registry),
            new \SiteAudit\Check\BestPracticesSites($registry),
            new \SiteAudit\Check\BestPracticesSitesDefault($registry),
            new \SiteAudit\Check\BestPracticesSitesSuperfluous($registry),

            // extensions
            new \SiteAudit\Check\ExtensionsCount($registry),
            new \SiteAudit\Check\ExtensionsDev($registry),
            new \SiteAudit\Check\ExtensionsDuplicate($registry),
            new \SiteAudit\Check\ExtensionsUnrecommended($registry),
        ];

        return $checks;
    }

    protected function checksForReport($reportId, $checks)
    {
        $result = [];

        foreach ($checks as $check) {
            if ($reportId == $check->getReportId()) {
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
            if ($check->getScore() != SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO) {
                $max += SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
                $score += $check->getScore();
            }
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
