<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\WatchdogSyslog
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Provides the WatchdogSyslog Check.
 */
class WatchdogSyslog extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'watchdog_syslog';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('syslog status');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check to see if syslog logging is enabled.");
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
  public function getResultFail() {
    return $this->t('Syslog logging is enabled!');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    if ($this->registry->syslog_enabled) {
      return $this->t('Syslog logging is enabled.');
    }
    return $this->t('Syslog logging is not enabled.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->getResultInfo();
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {}

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->getScore() == SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL && ($this->registry->vendor == 'pantheon')) {
      return $this->t('On Pantheon, you can technically write to syslog, but there is no mechanism for reading it. Disable syslog and enable dblog instead.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $this->registry->syslog_enabled = \Drupal::moduleHandler()->moduleExists('syslog');
    if ($this->registry->syslog_enabled) {
      if ($this->registry->vendor == 'pantheon') {
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
      }
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
