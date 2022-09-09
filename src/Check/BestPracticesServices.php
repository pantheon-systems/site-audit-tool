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
   * Whether the services.yml file exists.
   *
   * @var bool
   */
  protected $exists = FALSE;

  /**
   * Whether the services.yml file is a symlink.
   *
   * @var bool
   */
  protected $is_symlink = FALSE;

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
      if (is_link(DRUPAL_ROOT . '/sites/default/services.yml')) {
        $this->is_symlink = TRUE;
      }
      $this->exists = TRUE;
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
    if (!$this->exists) {
      return $this->t('services.yml does not exist! Copy the default.service.yml to services.yml and see https://www.drupal.org/documentation/install/settings-file for details.');
    }
    if ($this->is_symlink) {
      return $this->t('sites/default/services.yml is a symbolic link.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if (!$this->exists && !$this->is_symlink) {
      return $this->t('Create services.yml file inside sites/default directory by copying default.services.yml file. See https://www.drupal.org/documentation/install/settings-file for details.');
    }
    if ($this->exists && $this->is_symlink) {
      return $this->t('Don\'t rely on symbolic links for core configuration files; copy services.yml where it should be and remove the symbolic link.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    if ($this->exists && !$this->is_symlink) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
  }

}
