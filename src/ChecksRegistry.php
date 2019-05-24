<?php

namespace SiteAudit;

/**
 * Registry of checks.
 */
class ChecksRegistry {

  protected $checksList;

  /**
   * Add a check to our list
   */
  public function add($check) {
    $this->checksList[$check->getId()] = $check;
  }

  /**
   * Invoke another check's calculateScore() method if it is needed
   */
  public function checkInvokeCalculateScore($id) {
    if (!isset($this->checksList[$id])) {
      print "wanted to call $id but it does not exist\n" . var_export(array_keys($this->checksList), true) . "\n";
      return;
    }
    $check = $this->checksList[$id];
    $check->calculateScore();
  }

}
