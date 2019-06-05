<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\BestPracticesSites
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the BestPracticesSites Check.
 */
class BestPracticesSites extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'best_practices_sites';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('sites/sites.php');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check if multisite configuration file is a symbolic link.");
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
    if ($this->registry->multisite_enabled) {
      return $this->t('sites.php is not a symbolic link.');
    }
    else {
      return $this->t('sites.php does not exist.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->t('sites/sites.php is a symbolic link.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      return $this->t('Don\'t rely on symbolic links for core configuration files; copy sites.php where it should be and remove the symbolic link.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $path = DRUPAL_ROOT . '/sites/sites.php';
    // Note that 'file_exists' will return FALSE if the file exists, but is
    // a symbolic link that points at a file that does not exist. In this
    // instance, it is better to give the user the warning to not use symbolic
    // links rather than act as if the file is not there at all.
    $this->registry->multisite_enabled = file_exists($path) || is_link($path);
    if ($this->registry->multisite_enabled && is_link($path)) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

}
