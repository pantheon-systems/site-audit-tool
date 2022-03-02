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
   * @param mixed $opt_out
   *   Array of all skipped tests, or true if this test should be skipped.
   */
  public function __construct($registry, $options = [], $opt_out = false) {
    $this->registry = $registry;
    $this->options = $options;

    if (is_array($opt_out) && !empty($opt_out)) {
      $classname = (new \ReflectionClass($this))->getShortName();
      $this->optOut = in_array($classname, $opt_out);
    }

    if ($this->optOut) {
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
      return t('Opted-out in site configuration or settings.php file.');
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

  protected function simpleList($list, $listType = 'ul') {
    if ($this->registry->html) {
      return $this->simpleHtmlList($list, $listType);
    }
    $ret_val = '';
    foreach ($list as $value) {
      $ret_val .= '- ' . $value . PHP_EOL;
    }
    return $ret_val;
  }

  private function simpleHtmlList($list, $listType = 'ul') {
    $ret_val = "<$listType>";
    foreach ($list as $value) {
      $ret_val .= '<li>' . $value . '</li>';
    }
    $ret_val .= "</$listType>";

    return $ret_val;
  }

  protected function simpleKeyValueList($keyHeader, $valueHeader, $list) {
    if ($this->registry->html) {
      return $this->simpleHtmlKeyValueList($keyHeader, $valueHeader, $list);
    }

    $ret_val  = $keyHeader . ': ' . $valueHeader . PHP_EOL;
    $ret_val .= str_repeat('-', strlen($keyHeader) + strlen($valueHeader) + 2);
    foreach ($list as $key => $value) {
      $ret_val .= PHP_EOL;
      $ret_val .= "$key: $value";
    }
    return $ret_val;
  }

  private function simpleHtmlKeyValueList($keyHeader, $valueHeader, $list) {
    $ret_val = '<table class="table table-condensed">';
    $ret_val .= "<thead><tr><th>$keyHeader</th><th>$valueHeader</th></tr></thead>";
    $ret_val .= '<tbody>';
    foreach ($list as $key => $value) {
      $ret_val .= "<tr><td>$key</td><td>$value</td></tr>";
    }
    $ret_val .= '</tbody>';
    $ret_val .= '</table>';

    return $ret_val;
  }

  protected function linebreak() {
    if ($this->registry->html) {
      return '<br/>';
    }
    return PHP_EOL;
  }

  protected function rowsToKeyValueList($rows) {
    return array_map(
      function ($item) {
        return (string)$item;
      },
      array_column($rows, 1, 0)
    );
  }

}
