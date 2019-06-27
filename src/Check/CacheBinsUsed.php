<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\CacheBinsUsed
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the CacheBinsUsed Check.
 */
class CacheBinsUsed extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'cache_bins_used';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Used Bins');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Cache bins used by each service.");
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
    return $this->simpleKeyValueList($this->t('Bin'), $this->t('Class'), $this->registry->cache_bins_used);
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
    if (empty($this->registry->cache_bins_all)) {
      $container = \Drupal::getContainer();
      $services = $container->getServiceIds();

      $this->registry->cache_bins_all = [];
      $back_ends = preg_grep('/^cache\.backend\./', array_values($services));
      foreach ($back_ends as $backend) {
        $this->registry->cache_bins_all[$backend] = get_class($container->get($backend));
      }
    }

    foreach ($container->getParameter('cache_bins') as $service => $bin) {
      $backend_class = get_class($container->get($service)) . 'Factory';
      $backend = array_search($backend_class, $this->registry->cache_bins_all);
      $this->registry->cache_bins_used[$bin] = $backend;
    }

    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
