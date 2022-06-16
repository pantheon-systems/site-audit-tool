<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\BestPracticesFolderStructure
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the BestPracticesFolderStructure Check.
 */
class BestPracticesFolderStructure extends SiteAuditCheckBase {

  /**
   * @var string
   */
  private $infoMessage;

  /**
   * @var string
   */
  private $passMessage;

  /**
   * @var string
   */
  private $warningMessage;

  /**
   * @var string
   */
  private $actionMessage;

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'best_practices_folder_structure';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Folder Structure');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Checks if modules/contrib and modules/custom directory is present.");
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
  public function getResultInfo() {
    return $this->infoMessage;
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->passMessage;
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->warningMessage;
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if (!$this->actionMessage) {
      return null;
    }

    return $this->actionMessage . ' ' . $this->t('Moving modules may cause errors, so refer to https://www.drupal.org/node/183681 for information on how to best proceed.');
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $this->registry->contrib = is_dir(DRUPAL_ROOT . '/modules/contrib');
    $this->registry->custom = is_dir(DRUPAL_ROOT . '/modules/custom');
    if (!$this->registry->contrib || !$this->registry->custom) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

}
