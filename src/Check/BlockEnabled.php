<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\BlockEnabled
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the BlockEnabled Check.
 */
class BlockEnabled extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'block_enabled';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Block status');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check to see if enabled.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'block';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {
    return $this->t('Block caching is not enabled!');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    return $this->t('Block is not enabled.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('Block is enabled.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->t("Block is enabled, but there is no default theme. Consider disabling block if you don't need it.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {}

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    if (!\Drupal::service('module_handler')->moduleExists('block')) {
      $this->abort = TRUE;
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }
    $this->registry->theme_default = \Drupal::config('system.theme')->get('default');
    if (!$this->registry->theme_default) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

}
