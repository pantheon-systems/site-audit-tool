<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\ViewsCount
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the ViewsCount Check.
 */
class ViewsCount extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'views_count';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Count');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Number of enabled Views.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'views';
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
    $views_count = count($this->registry->views);
    if (!$views_count) {
      return $this->t('There are no enabled views.');
    }
    return $this->t('There are @count_views enabled views.', array(
      '@count_views' => count($this->registry->views),
    ));
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->getResultPass();
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      return $this->t('Consider disabling the views module if you don\'t need it.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {

    $this->checkInvokeCalculateScore('views_enabled');
    if (!$this->registry->views_enabled) {
      return;
    }

    $this->registry->views = array();

    $all_views = \Drupal::entityManager()->getListBuilder('view')->load();
    foreach ($all_views['enabled'] as $view) {
      $this->registry->views[] = $view;
    }

    if (empty($this->registry->views)) {
      $this->abort = TRUE;
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

}
