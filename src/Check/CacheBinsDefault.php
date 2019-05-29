<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\CacheBinsDefault.
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the CacheBinsDefault. Check.
 */
class CacheBinsDefault extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'cache_default_bins';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Default cache bins');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("All default cache bins.");
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
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    return $this->simpleKeyValueList($this->t('Bin'), $this->t('Class'), $this->registry->cache_default_backends);
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
    $container = \Drupal::getContainer();
    $defaults = $container->getParameter('cache_default_bin_backends');
    $this->registry->cache_default_backends = [];
    foreach ($container->getParameter('cache_bins') as $bin) {
      if (isset($defaults[$bin])) {
        $this->registry->cache_default_backends[$bin] = $defaults[$bin];
      }
      else {
        $this->registry->cache_default_backends[$bin] = 'cache.backend.database';
      }
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
