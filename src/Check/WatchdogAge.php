<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\WatchdogAge
 */
namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the WatchdogAge Check.
 */
class WatchdogAge extends SiteAuditCheckBase {
  public $ageNewest;
  public $ageOldest;

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'watchdog_age';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Date range of log entries');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Oldest and newest.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'watchdog';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    // If two different days...
    if (date('Y-m-d', $this->ageOldest) != date('Y-m-d', $this->ageNewest)) {
      return $this->t('From @from to @to (@days days)', array(
        '@from' => date('r', $this->ageOldest),
        '@to' => date('r', $this->ageNewest),
        '@days' => round(($this->ageNewest - $this->ageOldest) / 86400, 2),
      ));
    }
    // Same day; don't calculate number of days.
    return $this->t('From @from to @to', array(
      '@from' => date('r', $this->ageOldest),
      '@to' => date('r', $this->ageNewest),
    ));
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
    $this->checkInvokeCalculateScore('watchdog_enabled');
    if (!$this->registry->watchdog_enabled) {
      $this->ageNewest = 'n/a';
      return;
    }

    // Age of oldest entry.
    $query = db_select('watchdog');
    $query->addField('watchdog', 'timestamp');
    $query->orderBy('wid', 'ASC');
    $query->range(0, 1);
    $this->ageOldest = $query->execute()->fetchField();

    // Age of newest entry.
    $query = db_select('watchdog');
    $query->addField('watchdog', 'timestamp');
    $query->orderBy('wid', 'DESC');
    $query->range(0, 1);
    $this->ageNewest = $query->execute()->fetchField();

    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
