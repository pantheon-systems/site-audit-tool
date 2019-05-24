<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\BestPracticesFast404
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the BestPracticesFast404 Check.
 */
class BestPracticesFast404 extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'best_practices_fast_404';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Fast 404 pages');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check if enabled.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'best_practices';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('Fast 404 pages are enabled.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->t('Fast 404 pages are not enabled for any path.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      return $this->t('See https://github.com/drupal/drupal/blob/8.0.x/sites/default/default.settings.php#L572 for details on how to implement.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $config = \Drupal::config('system.performance');
    if ($config->get('fast_404.enabled') && trim($config->get('fast_404.paths')) != '') {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
  }

}
