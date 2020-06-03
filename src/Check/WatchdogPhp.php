<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\WatchdogPhp
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Database\Database;

/**
 * Provides the WatchdogPhp Check.
 */
class WatchdogPhp extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'watchdog_php';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('PHP messages');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Count PHP notices, warnings and errors.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'watchdog';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    $counts = array();
    foreach ($this->registry->php_counts as $severity => $count) {
      $counts[] = $severity . ': ' . $count;
    }
    $ret_val = implode(', ', $counts);
    $ret_val .= ' - total ' . $this->registry->percent_php . '%';
    return $ret_val;
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('No PHP warnings, notices or errors.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->getResultInfo();
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      return $this->t('Every time Drupal logs a PHP notice, warning or error, PHP executes slower and the writing operation locks the database. By eliminating the problems, your site will be faster.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $this->registry->php_counts = array();
    $this->registry->php_count_total = 0;
    $this->registry->percent_php = 0;

    $this->checkInvokeCalculateScore('watchdog_enabled');
    if (!$this->registry->watchdog_enabled) {
      return;
    }

    $query = Database::getConnection()->select('watchdog');
    $query->addExpression('COUNT(*)', 'count');
    $query->addField('watchdog', 'severity');
    $query->groupBy('severity');
    $query->orderBy('severity', 'ASC');
    $result = $query->execute();

    $severity_levels = $this->watchdog_severity_levels();
    while ($row = $result->fetchObject()) {
      $row->severity = $severity_levels[$row->severity];
      //$row = watchdog_format_result($result);
      if (!isset($this->registry->php_counts[$row->severity])) {
        $this->registry->php_counts[$row->severity] = 0;
      }
      $this->registry->php_counts[$row->severity]++;
      $this->registry->php_count_total++;
    }

    $this->registry->percent_php = round(($this->registry->php_count_total / $this->registry->count_entries) * 100, 2);
    if ($this->registry->percent_php >= 10) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

  /**
   * watchdog severity levels
   * @see drush_watchdog_severity_levels()
   */
  public function watchdog_severity_levels() {
    return array(
      RfcLogLevel::EMERGENCY => 'emergency',
      RfcLogLevel::ALERT => 'alert',
      RfcLogLevel::CRITICAL => 'critical',
      RfcLogLevel::ERROR => 'error',
      RfcLogLevel::WARNING => 'warning',
      RfcLogLevel::NOTICE => 'notice',
      RfcLogLevel::INFO => 'info',
      RfcLogLevel::DEBUG => 'debug',
    );
  }

}
