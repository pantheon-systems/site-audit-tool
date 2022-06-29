<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\ExtensionsDuplicate
 */

namespace SiteAudit\Check;

use SiteAudit\SiteAuditCheckBase;

/**
 * Provides the ExtensionsDuplicate Check.
 */
class ExtensionsDuplicate extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getId() {
    return 'extensions_duplicate';
  }

  /**
   * {@inheritdoc}.
   */
  public function getLabel() {
    return $this->t('Duplicates');
  }

  /**
   * {@inheritdoc}.
   */
  public function getDescription() {
    return $this->t("Check for duplicate extensions in the site codebase.");
  }

  /**
   * {@inheritdoc}.
   */
  public function getReportId() {
    return 'extensions';
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
    return $this->t('No duplicate extensions were detected.', array());
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    $ret_val = $this->t('The following duplicate extensions were found:');
    if ($this->registry->html) {
      $ret_val = '<p>' . $ret_val . '</p>';
      $ret_val .= '<table class="table table-condensed">';
      $ret_val .= '<thead><tr><th>' . dt('Name') . '</th><th>' . dt('Paths') . '</th></thead>';
      $ret_val .= '<tbody>';
      foreach ($this->registry->extensions_dupe as $name => $extension_infos) {
        $ret_val .= '<tr><td>' . $name . '</td>';
        $extension_list = array();
        foreach ($extension_infos as $extension_info) {
          $extension_list[] = $extension_info['label'];
        }
        $ret_val .= '<td>' . implode('<br/>', $extension_list) . '</td></tr>';
      }
      $ret_val .= '</tbody>';
      $ret_val .= '</table>';
    }
    else {
      foreach ($this->registry->extensions_dupe as $name => $extension_infos) {
        $ret_val .= PHP_EOL;
        // @todo: We need to inject a "json mode" option into the check
        // classes, since the global `drush_get_option` is no longer available.
        if (true /* !drush_get_option('json') */) {
          $ret_val .= str_repeat(' ', 6);
        }
        $ret_val .= $name . PHP_EOL;
        $extension_list = '';
        foreach ($extension_infos as $extension_info) {
          $extension_list .= str_repeat(' ', 8);
          $extension_list .= $extension_info['label'];
          $extension_list .= PHP_EOL;
        }
        $ret_val .= rtrim($extension_list);
      }
    }
    return $ret_val;
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score != SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS) {
      return $this->t('Prune your codebase to have only one copy of any given extension.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    $this->registry->extensions_dupe = array();
    $drupal_root = DRUPAL_ROOT;
    $settings = \Drupal::service('settings');
    $kernel = \Drupal::service('kernel');
    $command = "find $drupal_root -xdev -type f -name '*.info.yml' -o -path './" . $settings->get('file_public_path', $kernel->getSitePath() . '/files') . "' -prune";
    exec($command, $result);

    foreach ($result as $path) {
      $path_parts = explode('/', $path);
      $name = substr(array_pop($path_parts), 0, -9);
      // Safe duplicates.
      if (in_array($name, array(
        'drupal_system_listing_compatible_test',
        'drupal_system_listing_incompatible_test',
        'aaa_update_test',
      ))) {
        continue;
      }
      if (!isset($this->registry->extensions_dupe[$name])) {
        $this->registry->extensions_dupe[$name] = array();
      }
      $path = substr($path, strlen($drupal_root) + 1);
      $info = file($drupal_root . '/' . $path);
      if (!$info) {
        continue;
      }

      $label = $path;
      $version = '';
      foreach ($info as $line) {
        if (0 !== strpos($line, 'version:')) {
          continue;
        }

        $version_split = explode(':', $line);
        $version = trim(str_replace("'", '', $version_split[1]));
        $label = $path . ' (' . $version . ')';
        break;
      }

      $this->registry->extensions_dupe[$name][] = array(
        'label' => $label,
        'path' => $path,
        'version' => $version,
      );
    }

    $this->filterOutResult();

    // Review the detected extensions.
    $moduleHandler = \Drupal::service('module_handler');
    foreach ($this->registry->extensions_dupe as $extension => $instances) {
      $paths_in_profile = 0;
      $non_profile_index = 0;
      $test_extensions = 0;
      foreach ($instances as $index => $instance) {
        // Ignore if it is a test extension.
        if (strpos($instance['path'], '/tests/') !== FALSE) {
          $test_extensions++;
          continue;
        }

        if (strpos($instance['path'], 'profiles/') === 0) {
          $paths_in_profile++;
          continue;
        }

        $non_profile_index = $index;
      }
      // If every path is within an installation profile
      // or is a test extension, ignore.
      if ($paths_in_profile + $test_extensions == count($instances)) {
        unset($this->registry->extensions_dupe[$extension]);
        continue;
      }

      // Allow versions that are greater than what's in an installation profile
      // if that version is enabled.
      $extension_object = $this->registry->extensions[$extension];
      if ($paths_in_profile > 0 &&
          count($instances) - $paths_in_profile == 1 &&
          $moduleHandler->moduleExists($extension)  &&
          $extension_object->info['version'] == $instances[$non_profile_index]['version'] &&
          $instances[$non_profile_index]['version'] != '') {
        $skip = TRUE;
        foreach ($instances as $index => $info) {
          if ($index != $non_profile_index && $info['version'] != '') {
            if (version_compare($instances[$non_profile_index]['version'], $info['version']) < 1) {
              $skip = FALSE;
              break;
            }
          }
          elseif ($info['version'] == '') {
            $skip = FALSE;
            break;
          }
        }
        if ($skip === TRUE) {
          unset($this->registry->extensions_dupe[$extension]);
        }
      }
    }

    // Determine score.
    if (count($this->registry->extensions_dupe)) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

  /**
   * Filters out $this->registry->extensions_dupe items with no duplicates or
   * with invalid *.info.yml file scheme.
   */
  private function filterOutResult() {
    $drupal_root = DRUPAL_ROOT;

    $this->removeNonDuplicates();

    foreach ($this->registry->extensions_dupe as $extension => $instances) {
      foreach ($instances as $index => $instance) {
        $info_file = file_get_contents($drupal_root . '/' . $instance['path']);
        if (false === $info_file) {
          continue;
        }

        // Validate *.info.yml to have "name:" and "type:" properties.
        if (!preg_match('/name:.+type:/s', $info_file)) {
          unset($this->registry->extensions_dupe[$extension][$index]);
        }
      }
    }

    $this->removeNonDuplicates();
  }

  /**
   * Removes non duplicates from $this->registry->extensions_dupe array.
   */
  private function removeNonDuplicates() {
    $this->registry->extensions_dupe = array_filter(
      $this->registry->extensions_dupe,
      function ($instances) {
        return count($instances) > 1;
      });
  }
}
