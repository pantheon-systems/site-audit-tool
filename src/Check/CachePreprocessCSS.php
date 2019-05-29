<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\CachePreprocessCSS
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use SiteAudit\Util\SiteAuditEnvironment;

/**
 * Provides the CachePreprocessCSS Check.
 */
class CachePreprocessCSS extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'cache_preprocess_css';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Aggregate and compress CSS files in Drupal');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Verify that Drupal is aggregating and compressing CSS.");
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
    return $this->t('CSS aggregation and compression is not enabled!');
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
    return $this->t('CSS aggregation and compression is enabled.');
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
      return $this->t('Go to /admin/config/development/performance and check "Aggregate and compress CSS files".');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $config = \Drupal::config('system.performance')->get('css.preprocess');
    if ($config) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    if (SiteAuditEnvironment::isDev()) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
  }

}
