<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\WatchdogEnabled
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the WatchdogEnabled Check.
 */
class WatchdogEnabled extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'watchdog_enabled';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('dblog status');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check to see if database logging is enabled.");
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
    return $this->t('Database logging (dblog) is not enabled; if the site is having problems, consider enabling it for debugging.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('Database logging (dblog) is enabled.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {}

  /**
   * {@inheritdoc}.
   */
  public function getAction() {}

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    if (!\Drupal::moduleHandler()->moduleExists('dblog')) {
      $this->registry->watchdog_enabled = FALSE;
      $this->abort = TRUE;
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }
    $this->registry->watchdog_enabled = TRUE;
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

}
