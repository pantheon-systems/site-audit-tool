<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\CachePreprocessJS
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use SiteAudit\Util\SiteAuditEnvironment;

/**
 * Provides the CachePreprocessJS Check.
 */
class CachePreprocessJS extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'cache_preprocess_js';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Aggregate and compress JavaScript files in Drupal');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Verify that Drupal is aggregating JavaScript.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'cache';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {
    return $this->t('JavaScript aggregation is not enabled!');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    return $this->getResultFail();
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('JavaScript aggregation is enabled.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {}

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if (!in_array($this->score, array(SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS))) {
      return $this->t('Go to /admin/config/development/performance and check "Aggregate JavaScript files".');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $config = \Drupal::config('system.performance')->get('js.preprocess');
    if ($config) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    if (SiteAuditEnvironment::isDev()) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
  }

}
