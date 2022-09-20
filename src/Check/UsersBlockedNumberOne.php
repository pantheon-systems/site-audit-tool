<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\UsersBlockedNumberOne
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use Drupal\Core\Database\Database;

/**
 * Provides the UsersBlockedNumberOne Check.
 */
class UsersBlockedNumberOne extends SiteAuditCheckBase {

  /**
   * Whether UID #1 is blocked.
   *
   * 1 = UID #1 is not blocked.
   * 0 = UID #1 is blocked.
   *
   * @var bool
   */
  protected $status;

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'users_blocked_number_one';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('UID #1 access');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Determine if UID #1 is blocked.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'users';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {
    return $this->t('UID #1 should be blocked, but is not.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    // If the result is passed but the account is unblocked, the request must
    // have come from inside the platform.
    if ($this->status == 1) {
      return $this->t('UID #1 is not blocked. Blocking this user eliminates a potential security risk.');
    }
    else {
      return $this->t('UID #1 is blocked, as recommended.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {}

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score != SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS) {
      return $this->t('Block UID #1');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {

    $query = Database::getConnection()->select('users_field_data', 'ufd');
    $query->addField('ufd', 'status');
    $query->condition('uid', 1);

    if (!$query->execute()->fetchField()) {
      // UID 1 is blocked or otherwise disabled.
      $this->status = 0;
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    else {
      // UID 1 is active.
      $this->status = 1;
      // If UID 1 is active, but the request is made from Pantheon, pass.
      if ($this->registry->vendor == "pantheon") {
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
      }

      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
    }
  }

}
