<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\UsersCountBlocked
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the UsersCountBlocked Check.
 */
class UsersCountBlocked extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'users_count_blocked';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Count Blocked');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Total number of blocked Drupal users.");
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
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    switch ($this->registry->count_users_blocked) {
      case 0:
        return $this->t('There are no blocked users.');
      break;
    default:
      return $this->formatPlural($this->registry->count_users_blocked, 'There is one blocked user.', 'There are @count blocked users.');
      break;
    }
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
    $query = db_select('users_field_data', 'ufd');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('status', 0);

    $this->registry->count_users_blocked = $query->execute()->fetchField();
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
