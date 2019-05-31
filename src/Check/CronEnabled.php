<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\CronEnabled
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the CronEnabled Check.
 */
class CronEnabled extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'cron_enabled';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Enabled');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check to see if cron is scheduled to run.");
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
  public function getResultFail() {
    return $this->t('You have disabled cron, which will prevent routine system tasks from executing.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    // Manual execution.
    if ($this->registry->cron_safe_threshold === 0) {
      return $this->t('Drupal Cron frequency is set to never, but has been executed within the past 24 hours (either manually or using drush cron).');
    }
    // Default.
    return $this->t('Cron is set to run every @minutes minutes.', array(
      '@minutes' => round($this->registry->cron_safe_threshold / 60),
    ));
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    if ($this->registry->cron_safe_threshold > (24 * 60 * 60)) {
      return $this->t('Drupal Cron frequency is set to mare than 24 hours.');
    }
    else {
      return $this->t('Drupal Cron has not run in the past day even though it\'s frequency has been set to less than 24 hours.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL) {
      return $this->t('Please visit /admin/config/system/cron and set the cron frequency to something other than Never but less than 24 hours.');
    }
    elseif ($this->score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      if ($this->registry->cron_safe_threshold > (24 * 60 * 60)) {
        return $this->t('Please visit /admin/config/system/cron and set the cron frequency to something less than 24 hours.');
      }
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    // Determine when cron last ran.
    $this->registry->cron_last = \Drupal::state()->get('system.cron_last');
    // Usually we'd just fetch this with \Drupal::config('automated_cron.settings')->get('interval');
    // However, Drush goes out of its way to hide the interval (make it appear to be '0') to avoid
    // cron runs during Drush commands. We can still access the correct value via the raw data though.
    $rawData = \Drupal::config('automated_cron.settings')->getRawData();
    $this->registry->cron_safe_threshold = isset($rawData['interval']) ? $rawData['interval'] : 0;

    // Cron hasn't run in the past day.
    if ((time() - $this->registry->cron_last) > (24 * 60 * 60)) {
      if ($this->registry->cron_safe_threshold === 0) {
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
      }
      else {
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
      }
    }
    elseif ($this->registry->cron_safe_threshold > (24 * 60 * 60)) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

}
