<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\DatabaseEngine
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;
use Drupal\Core\Database\Database;

/**
 * Provides the Database InnoDB check.
 */
class DatabaseEngine extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'database_engine';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Storage Engines');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check to see if there are any tables that aren't using InnoDB.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'database';
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {
    return $this->simpleKeyValueList($this->t('Table Name'), $this->t('Engine'), $this->registry->engine_tables);
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('Every table is using InnoDB.');
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
      return $this->t('Change the Storage Engine to InnoDB. See @url for details.', array(
        '@url' => 'http://dev.mysql.com/doc/refman/5.6/en/converting-tables-to-innodb.html',
      ));
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $connection = \Drupal\Core\Database\Database::getConnection();
    $query = Database::getConnection()->select('information_schema.TABLES', 'ist');
    $query->addField('ist', 'TABLE_NAME', 'name');
    $query->addField('ist', 'ENGINE', 'engine');
    $query->condition('ist.ENGINE', 'InnoDB', '<>');
    $query->condition('ist.table_schema', $connection->getConnectionOptions()['database']);
    $result = $query->execute();
    $count = 0;
    while ($row = $result->fetchAssoc) {
      $count++;
      $this->registry->engine_tables[$row['name']] = $row['engine'];
    }
    if ($count === 0) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
  }

}
