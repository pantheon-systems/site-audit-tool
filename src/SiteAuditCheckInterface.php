<?php

namespace SiteAudit;

/**
 * Defines an interface for Site Audit Check plugins.
 */
interface SiteAuditCheckInterface {

  /**
   * Determine the result message based on the score.
   *
   * @return string
   *   Human readable message for a given status.
   */
  public function getResult();

  /**
   * Get a human readable label for a score.
   *
   * @return string
   *   Pass, Recommendation and so forth.
   */
  public function getScoreLabel();

  /**
   * Get the ID or machine name for the check.
   *
   * @return string
   *   The ID or machine name for the check.
   */
  public function getId();

  /**
   * Get the label for the check that describes, high level what is happening.
   *
   * @return string
   *   Get the label for the check that describes, high level what is happening.
   */
  public function getLabel();

  /**
   * Get a more verbose description of what is being checked.
   *
   * @return string
   *   A sentence describing the check; shown in detail mode.
   */
  public function getDescription();

  /**
   * Get the report id for the report this check should be included in.
   *
   * @return string
   *   The report id for this check.
   */
  public function getReportId();

  /**
   * Get the description of what happened in a failed check.
   *
   * @return string
   *   Something is explicitly wrong and requires action.
   */
  public function getResultFail();

  /**
   * Get the result of a purely informational check.
   *
   * @return string
   *   Purely informational response.
   */
  public function getResultInfo();

  /**
   * Get a description of what happened in a passed check.
   *
   * @return string
   *   Success; good job.
   */
  public function getResultPass();

  /**
   * Get a description of what happened in a warning check.
   *
   * @return string
   *   Something is wrong, but not horribly so.
   */
  public function getResultWarn();

  /**
   * Get action items for a user to perform if the check did not pass.
   *
   * @return string
   *   Actionable tasks to perform.
   */
  public function getAction();

  /**
   * Display action items for a user to perform.
   *
   * @return string
   *   Actionable tasks to perform, or nothing if check is opted-out.
   */
  public function renderAction();

  /**
   * Calculate the score.
   *
   * @return int
   *   Constants indicating pass, fail and so forth.
   */
  public function calculateScore();

  /**
   * Get a quantifiable number representing a check result; lazy initialization.
   *
   * @return int
   *   Constants indicating pass, fail and so forth.
   */
  public function getScore();

  /**
   * Get the check registry.
   *
   * @return array
   *   Contains values calculated from this check and any prior checks.
   */
  public function getRegistry();

  /**
   * Determine whether the check failed so badly that the report must stop.
   *
   * @return bool
   *   Whether to stop the abort after this check.
   */
  public function shouldAbort();

  /**
   * Get the report percent override, if any.
   *
   * @return int
   *   The overridden percentage.
   */
  public function getPercentOverride();

}
