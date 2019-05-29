<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\DatabaseRowCount
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the Database Row Count check.
 */
class DatabaseRowCount extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'database_row_count';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Tables with at least 1000 rows');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Return list of all tables with at least 1000 rows in the database.");
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
  public function getResultInfo() {
    if (empty($this->registry->rows_by_table)) {
      return $this->t('No tables with more than 1000 rows.');
    }
    return $this->simpleKeyValueList($this->t('Table Name'), $this->t('Rows'), $this->registry->rows_by_table);
    $table_rows = [];
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->getResultInfo();
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {}

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $connection = \Drupal\Core\Database\Database::getConnection();
    $this->registry->rows_by_table = array();
    $warning = FALSE;
    $query = db_select('information_schema.TABLES', 'ist');
    $query->fields('ist', array('TABLE_NAME', 'TABLE_ROWS'));
    $query->condition('ist.TABLE_ROWS', 1000, '>');
    $query->condition('ist.table_schema', $connection->getConnectionOptions()['database']);
    $query->orderBy('TABLE_ROWS', 'DESC');
    $result = $query->execute()->fetchAllKeyed();
    foreach ($result as $table => $rows) {
      if ($rows > 1000) {
        $warning = TRUE;
      }
      $this->registry->rows_by_table[$table] = $rows;
    }
    if ($warning) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}
