<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\BestPracticesServices
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the BestPracticesServices Check.
 */
class BestPracticesServices extends SiteAuditCheckBase {

  /**
   * Whether the services.yml file is a symlink.
   *
   * 0 = Does not exist.
   * 1 = Exists, but is symlink.
   * 2 = Exists and is not symlink.
   *
   * @var int
   */
  protected $status = 0;

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'best_practices_services';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('sites/default/services.yml');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check if the services file exists.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'best_practices';
  }

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    if ($this->optOut) {
      return t('Opted-out in site configuration or settings.php file.');
    }

    if (file_exists(DRUPAL_ROOT . '/sites/default/services.yml')) {
      // File exists, but is symlink.
      if (is_link(DRUPAL_ROOT . '/sites/default/services.yml')) {
        $this->status = 1;
        $this->score = SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
      }
      // File exists, and is not symlink.
      else {
        $this->status = 2;
        $this->score = SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
      }
    }
    // File does not exist.
    else {
      $this->score = SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }

    switch ($this->score) {
      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS:
        return $this->getResultPass();

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN:
        return $this->getResultWarn();

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO:
        return $this->getResultInfo();

      default:
        return $this->getResultFail();
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('services.yml exists and is not a symbolic link.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    if ($this->status == 0) {
      return $this->t('services.yml does not exist! Copy the default.service.yml to services.yml and see https://www.drupal.org/documentation/install/settings-file for details.');
    }
    if ($this->status == 1) {
      return $this->t('sites/default/services.yml is a symbolic link.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->status == 0) {
      return $this->t('Create services.yml file inside sites/default directory by copying default.services.yml file. See https://www.drupal.org/documentation/install/settings-file for details.');
    }
    if ($this->status == 1) {
      return $this->t('Don\'t rely on symbolic links for core configuration files; copy services.yml where it should be and remove the symbolic link.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    switch($this->status) {
      case 2:
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
      default:
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
  }

}
