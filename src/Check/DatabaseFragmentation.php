<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\DatabaseFragmentation
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the Database Fragmentation check.
 */
class DatabaseFragmentation extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'database_fragmentation';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Database Fragmentation');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Detect table fragmentation which increases storage space and decreases I/O efficiency.");
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
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->simpleKeyValueList($this->t('Table Name'), $this->t('Fragmentation Ratio'), $this->registry->database_fragmentation);
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->getScore() == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      return $this->t('Run "OPTIMIZE TABLE" on the fragmented tables. Refer to https://dev.mysql.com/doc/en/optimize-table.html for more details.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $connection = \Drupal\Core\Database\Database::getConnection();
    $query = db_select('information_schema.TABLES', 'ist');
    $query->fields('ist', array('TABLE_NAME'));
    $query->addExpression('ROUND(DATA_LENGTH / 1024 / 1024)', 'data_length');
    $query->addExpression('ROUND(INDEX_LENGTH / 1024 / 1024)', 'index_length');
    $query->addExpression('ROUND(DATA_FREE / 1024 / 1024)', 'data_free');
    $query->condition('ist.DATA_FREE', 0, '>');
    $query->condition('ist.table_schema', $connection->getConnectionOptions()['database']);
    $result = $query->execute();
    while ($row = $result->fetchAssoc()) {
      $data = $row['data_length'] + $row['index_length'];
      if ($data != 0) {
        $free = $row['data_free'];
        $fragmentation_ratio = $free / $data;
        if ($fragmentation_ratio > 0.05) {
          $this->registry->database_fragmentation[$row['TABLE_NAME']] = $fragmentation_ratio;
        }
      }
    }
    if (empty($this->registry->database_fragmentation)) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
  }

}
