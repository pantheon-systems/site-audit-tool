<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\CachePageExpire
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use SiteAudit\Util\SiteAuditEnvironment;

/**
 * Provides the CachePageExpire Check.
 */
class CachePageExpire extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'cache_page_expire';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Expiration of cached pages');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Verify that Drupal\'s cached pages last for at least 15 minutes.");
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
    return $this->t('Expiration of cached pages not set!');
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
    return $this->t('Expiration of cached pages is set to @minutes min.', array(
      '@minutes' => round(\Drupal::config('system.performance')->get('cache.page.max_age') / 60),
    ));
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->t('Expiration of cached pages only set to @minutes min.', array(
      '@minutes' => round(\Drupal::config('system.performance')->get('cache.page.max_age') / 60),
    ));
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {}

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $config = \Drupal::config('system.performance')->get('cache.page.max_age');
    if ($config == 0) {
      if (SiteAuditEnvironment::isDev()) {
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
      }
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
    }
    elseif ($config >= 900) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
  }

}
