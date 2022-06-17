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
    $subDirs = glob(DRUPAL_ROOT . '/modules/*', GLOB_ONLYDIR);
    if (!$subDirs) {
      // No subdirectories inside "modules/" directory found.
      $this->infoMessage = $this->t('Contrib and custom modules not found.');

      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }

    $validContribModulesDir = $this->getValidDirName($subDirs, array('contrib', 'composer'));
    $validCustomModulesDir = $this->getValidDirName($subDirs, array('custom'));
    if (1 === count($subDirs)) {
      // Only one subdirectory inside "modules/" directory found.
      if ($validContribModulesDir || $validCustomModulesDir) {
        $this->passMessage = $this->t(
          'modules/@subdir directory exist.',
          array('@subdir' => basename($subDirs[0]))
        );

        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
      }

      $this->warningMessage = $this->t('Either modules/contrib or modules/custom directories are not present!');
      $this->actionMessage = $this->t('Put all the contrib modules inside the ./modules/contrib directory or the custom modules inside the ./modules/custom directory.');

      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }

    // Two or more subdirectories inside "modules/" directory found.
    if (!$validContribModulesDir && !$validCustomModulesDir) {
      $this->warningMessage = $this->t('Neither modules/contrib nor modules/custom directories are present!');
      $this->actionMessage = $this->t('Put all the contrib modules inside the ./modules/contrib directory and custom modules inside the ./modules/custom directory.');

      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }

    if (!$validContribModulesDir) {
      $this->warningMessage = $this->t('modules/contrib directory is not present!');
      $this->actionMessage = $this->t('Put all the contrib modules inside the ./modules/contrib directory.');

      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }

    if (!$validCustomModulesDir) {
      $this->warningMessage = $this->t('modules/custom directory is not present!');
      $this->actionMessage = $this->t('Put all the custom modules inside the ./modules/custom directory.');

      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }

    $this->passMessage = $this->t(
      'modules/@contrib_subdir and modules/@custom_subdir directories exist.',
      array('@contrib_subdir' => $validContribModulesDir, '@custom_subdir' => $validCustomModulesDir)
    );

    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

  /**
   * Returns the valid subdirectory name if found.
   *
   * @param array $subDirs
   * @param array $validDirNames
   *
   * @return string|false
   */
  private function getValidDirName($subDirs, $validDirNames)
  {
    $filteredSubDirs = array_filter($subDirs, function($subDir) use ($validDirNames) {
      $subDirName = basename($subDir);

      return in_array($subDirName, $validDirNames, true);
    });

    if (!$filteredSubDirs)  {
      return false;
    }

    return basename(reset($filteredSubDirs));
  }

}
