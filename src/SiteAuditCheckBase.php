<?php

namespace SiteAudit;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Site Audit Check plugins.
 */
abstract class SiteAuditCheckBase implements SiteAuditCheckInterface {

  use StringTranslationTrait;

  const AUDIT_CHECK_SCORE_INFO = 3;
  const AUDIT_CHECK_SCORE_PASS = 2;
  const AUDIT_CHECK_SCORE_WARN = 1;
  const AUDIT_CHECK_SCORE_FAIL = 0;

  /**
   * Quantifiable number associated with result on a scale of 0 to 2.
   *
   * @var int
   */
  protected $score;

  /**
   * Names of checks that should not run as a result of this check.
   *
   * @var array
   */
  protected $abort = [];

  /**
   * User has opted out of this check in configuration.
   *
   * @var bool
   */
  protected $optOut = FALSE;

  /**
   * If set, will override the Report's percentage.
   *
   * @var int
   */
  protected $percentOverride;

  /**
   * Use for passing data between checks within a report.
   *
   * @var array
   */
  protected $registry;

  /**
   * are we in a static context
   *
   * @var boolean
   */
  protected $static = TRUE;

  /**
   * options passed in for reports and checks
   *
   * @var array
   */
  protected $options = [];

  /**
   * Constructor.
   *
   * @param array $registry
   *   Aggregates data from each individual check.
   * @param array $options
   *   Options.
   * @param bool $opt_out
   *   If set, will not perform checks.
   */
  public function __construct($registry, $options = [], $opt_out = false) {
    $this->registry = $registry;
    $this->options = $options;
    $this->optOut = $opt_out;
    if ($opt_out) {
      $this->score = SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }
    $static = FALSE;

    // Not ideal, but store a reference to ourself in the registry checks list
    $this->registry->checksList->add($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    if ($this->optOut) {
      return t('Opted-out in site configuration.');
    }
    switch ($this->score) {
      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS:
        return $this->getResultPass();

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN:
        return $this->getResultWarn();

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO:
        return $this->getResultInfo();

      default:
        return $this->getResultFail();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScoreLabel() {
    switch ($this->score) {
      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS:
        return $this->t('Pass');

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN:
        return $this->t('Warning');

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO:
        return $this->t('Information');

      default:
        return $this->t('Blocker');

    }
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getId();

  /**
   * {@inheritdoc}
   */
  abstract public function getLabel();

  /**
   * {@inheritdoc}
   */
  abstract public function getDescription();

  /**
   * {@inheritdoc}
   */
  abstract public function getReportId();

  /**
   * {@inheritdoc}
   */
  abstract public function getResultFail();

  /**
   * {@inheritdoc}
   */
  abstract public function getResultInfo();

  /**
   * {@inheritdoc}
   */
  abstract public function getResultPass();

  /**
   * {@inheritdoc}
   */
  abstract public function getResultWarn();

  /**
   * {@inheritdoc}
   */
  abstract public function getAction();

  /**
   * {@inheritdoc}
   */
  public function renderAction() {
    if ($this->optOut) {
      return '';
    }
    return $this->getAction();
  }

  /**
   * {@inheritdoc}
   */
  abstract public function calculateScore();

  /**
   * {@inheritdoc}
   */
  public function getScore() {
    if (!isset($this->score)) {
      $this->score = $this->calculateScore();
    }
    return $this->score;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistry() {
    return $this->registry;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldAbort() {
    return $this->abort;
  }

  /**
   * {@inheritdoc}
   */
  public function getPercentOverride() {
    return $this->percentOverride;
  }

  /**
   * invoke another check's calculateScore() method if it is needed
   */
  protected function checkInvokeCalculateScore($id) {
    $this->registry->checksList->checkInvokeCalculateScore($id);
  }

}
