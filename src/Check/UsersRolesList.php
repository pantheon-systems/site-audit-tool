<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\UsersRolesList
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use Drupal\Core\Database\Database;

/**
 * Provides the UsersRolesList Check.
 */
class UsersRolesList extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'users_roles_list';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('List Roles');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Show all available roles and user counts.");
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
    $counts = array();
    foreach ($this->registry->roles as $name => $count_users) {
      $counts[] = "$name: $count_users";
    }
    return implode($this->linebreak(), $counts);
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
    $query = Database::getConnection()->select('user__roles');
    $query->addExpression('COUNT(entity_id)', 'count');
    $query->addfield('user__roles', 'roles_target_id', 'name');
    $query->groupBy('name');
    $query->orderBy('name', 'ASC');
    $results = $query->execute();
    while ($row = $results->fetchObject()) {
      $this->registry->roles[$row->name] = $row->count;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
