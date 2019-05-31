<?php

namespace SiteAudit;

use Symfony\Component\Filesystem\Filesystem;

use Drush\TestTraits\DrushTestTrait;

/**
 * Convenience class for creating fixtures.
 */
class Fixtures
{
  use DrushTestTrait;

  protected static $fixtures = null;
  protected $installed = false;

  public static function instance()
  {
      if (!static::$fixtures) {
          static::$fixtures = new self();
      }
      return static::$fixtures;
  }

  public function createSut()
  {
      // Skip install if already installed.
      if ($this->installed || getenv('SI_SKIP')) {
          return;
      }
      // @todo: pull db credentials from phpunit.xml configuration

      // Make settings.php writable again
      chmod('sut/web/sites/default/', 0755);
      @unlink('sut/web/sites/default/settings.php');
      copy('sut/web/sites/default/default.settings.php', 'sut/web/sites/default/settings.php');

      // Run site-install (Drupal makes settings.php unwritable)
      $this->drush('site-install', [], ['db-url' => 'mysql://root@127.0.0.1/siteaudittooldb']);

      $this->installed = true;
  }

  /**
   * Directories to delete when we are done.
   *
   * @var string[]
   */
  protected $tmpDirs = [];

  /**
   * Gets the path to the project fixtures.
   *
   * @return string
   *   Path to project fixtures
   */
  public function allFixturesDir()
  {
    return realpath(__DIR__ . '/fixtures');
  }

  /**
   * Generates a path to a temporary location, but do not create the directory.
   *
   * @param string $extraSalt
   *   Extra characters to throw into the md5 to add to name.
   *
   * @return string
   *   Path to temporary directory
   */
  public function tmpDir($extraSalt = '')
  {
    $tmpDir = sys_get_temp_dir() . '/site-audit-test-' . md5($extraSalt . microtime());
    $this->tmpDirs[] = $tmpDir;
    return $tmpDir;
  }

  /**
   * Creates a temporary directory.
   *
   * @param string $extraSalt
   *   Extra characters to throw into the md5 to add to name.
   *
   * @return string
   *   Path to temporary directory
   */
  public function mkTmpDir($extraSalt = '')
  {
    $tmpDir = $this->tmpDir($extraSalt);
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($tmpDir);
    return $tmpDir;
  }

  /**
   * Calls 'tearDown' in any test that copies fixtures to transient locations.
   */
  public function tearDown()
  {
    // Remove any temporary directories that were created.
    $filesystem = new Filesystem();
    foreach ($this->tmpDirs as $dir) {
      $filesystem->remove($dir);
    }
    // Clear out variables from the previous pass.
    $this->tmpDirs = [];
    $this->io = NULL;
  }

}
