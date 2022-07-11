<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\BestPracticesSitesSuperfluous
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the BestPracticesSitesSuperfluous Check.
 */
class BestPracticesSitesSuperfluous extends SiteAuditCheckBase {

  /**
   * @var string[]
   */
  private $allowedFiles = array(
    '.DS_Store',
    '.gitignore',
    'all',
    'default',
    'development.services.yml',
    'example.settings.local.php',
    'example.sites.php',
    'README.txt',
  );

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'best_practices_sites_superfluous';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Superfluous files in /sites');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t('Detect unnecessary files.');
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
    return $this->t('No unnecessary files detected.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->t('The following extra files were detected: @list', array(
      '@list' => implode(', ', $this->registry->superfluous),
    ));
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score === self::AUDIT_CHECK_SCORE_WARN) {
      return $this->t('Unless you have an explicit need for it, don\'t store anything other than settings here.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $this->registry->superfluous = array();
    $sites_dir = DRUPAL_ROOT . DIRECTORY_SEPARATOR . 'sites';
    $files = @scandir($sites_dir) ?: array();
    foreach ($files as $file) {
      if (!is_file($sites_dir . DIRECTORY_SEPARATOR . $file)) {
        continue;
      }

      if ($this->isAllowedFile($file)) {
        continue;
      }

      if ($file === 'sites.php' && $this->registry->vendor !== 'pantheon') {
        // Support multi-site directory aliasing for non-Pantheon sites.
        continue;
      }

      $this->registry->superfluous[] = $file;
    }

    return count($this->registry->superfluous) > 0
      ? self::AUDIT_CHECK_SCORE_WARN
      : self::AUDIT_CHECK_SCORE_PASS;
  }

  /**
   * Returns TRUE if the file is an allowed file.
   *
   * @param string $file
   *   File name.
   *
   * @return bool
   */
  private function isAllowedFile($file) {
    return in_array($file, $this->allowedFiles, true);
  }
}
