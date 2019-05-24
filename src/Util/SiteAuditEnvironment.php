<?php

/**
 * @file
 * Contains Site Audit environment utilities
 */

namespace SiteAudit\Util;

class SiteAuditEnvironment {

  /**
   * Determine if in a development environment.
   *
   * @return bool
   *   Whether the site is in a development environment.
   */
  public static function isDev() {
    // Acquia.
    if (defined('AH_SITE_ENVIRONMENT')) {
      return !in_array(PANTHEON_ENVIRONMENT, array('test', 'prod'));
    }

    // Pantheon.
    if (defined('PANTHEON_ENVIRONMENT')) {
      return !in_array(PANTHEON_ENVIRONMENT, array('test', 'live'));
    }

    return FALSE;
  }

}
