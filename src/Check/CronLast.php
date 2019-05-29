<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\CronLast
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the CronLast Check.
 */
class CronLast extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'cron_last';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Last run');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Time Cron last executed.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'cron';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    if ($this->registry->cron_last) {
      return $this->t('Cron last ran at @date (@ago ago)', array(
        '@date' => date('r', $this->registry->cron_last),
        '@ago' => \Drupal::service('date.formatter')->formatInterval(time() - $this->registry->cron_last),
      ));
    }
    return $this->t('Cron has never run.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {}

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
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
