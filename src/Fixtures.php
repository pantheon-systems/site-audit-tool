<?php

namespace SiteAudit;

/**
 * Simple test fixtures for provisioning a Drupal SUT.
 *
 * Compatible with PHP 5.6+ (no scalar/nullable typehints or return types).
 */
class Fixtures
{
    /** @var Fixtures */
    private static $singleton;

    /** @var string */
    private $repoRoot;

    /** @var string */
    private $webRoot;

    private function __construct()
    {
        // Repo root: ../ from src/
        $this->repoRoot = dirname(__DIR__);
        $this->webRoot  = $this->repoRoot . '/sut/web';
    }

    /**
     * Backwards-compatible singleton (some scripts call ::instance()).
     *
     * @return Fixtures
     */
    public static function instance()
    {
        if (!self::$singleton) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }

    /**
     * Main entry used by tests and CI steps.
     * Ensures the scaffold exists, exports Drush env, and installs Drupal once.
     *
     * @param array $options (reserved for future use)
     * @return Fixtures
     */
    public static function createSut($options = array())
    {
        $f = self::instance();
        $f->ensureSutScaffold();
        $f->exportDrushEnv();
        $f->ensureSutInstalled();
        return $f;
    }

    /**
     * Convenience for debugging / existing scripts.
     *
     * @return string
     */
    public function dbUrl()
    {
        $url = getenv('UNISH_DB_URL');
        return $url ? $url : '';
    }

    /**
     * Absolute repo root (useful for debugging).
     *
     * @return string
     */
    public function repoRoot()
    {
        return $this->repoRoot;
    }

    /**
     * Absolute Drupal web root (sut/web).
     *
     * @return string
     */
    public function webRoot()
    {
        return $this->webRoot;
    }

    /**
     * Ensure sut/web exists and looks like a Drupal root.
     *
     * @throws \RuntimeException
     */
    private function ensureSutScaffold()
    {
        if (!is_dir($this->webRoot)) {
            $gw = getenv('GITHUB_WORKSPACE');
            $expected = $gw ? $gw . '/sut/web' : $this->webRoot;
            throw new \RuntimeException(
                "Expected Drupal root at {$expected}. Run the scenario install step that provisions 'sut/web' before tests."
            );
        }
        if (!is_file($this->webRoot . '/index.php')) {
            throw new \RuntimeException(
                "Drupal root found at {$this->webRoot} but it does not look scaffolded (missing index.php)."
            );
        }
    }

    /**
     * Export Drush environment so any subsequent drush calls (including from tests)
     * default to the SUT.
     */
    private function exportDrushEnv()
    {
        // Make Drush default to the right site even if cwd is repo root.
        putenv('DRUSH_OPTIONS_ROOT=' . $this->webRoot);
        putenv('DRUSH_OPTIONS_URI=http://default');
    }

    /**
     * Install the site once if not bootstrapped yet.
     *
     * @throws \RuntimeException
     */
    private function ensureSutInstalled()
    {
        if ($this->isBootstrapped()) {
            return;
        }

        $dbUrl = $this->dbUrl();
        if (!$dbUrl) {
            throw new \RuntimeException('UNISH_DB_URL is not set; cannot install Drupal.');
        }

        // Let Drush handle DB create if needed; most CI already pre-creates it.
        $cmd = $this->drushCmd(
            "si -y --db-url='" . addslashes($dbUrl) .
            "' --account-name=admin --account-pass=admin --site-name=SUT standard"
        );
        $this->execMust($cmd, "Drush site:install failed");

        // Verify we really bootstrapped.
        if (!$this->isBootstrapped()) {
            $status = $this->drushCapture('status --fields=bootstrap,db-status,db-driver,db-hostname,db-name --format=json');
            throw new \RuntimeException("Drupal still did not bootstrap after install. drush status:\n" . $status);
        }
    }

    /**
     * Check if the SUT is bootstrapped (installed).
     *
     * @return bool
     */
    private function isBootstrapped()
    {
        $json = $this->drushCapture('status --fields=bootstrap --format=json');
        $data = json_decode($json, true);

        if (is_array($data)) {
            if (isset($data['bootstrap']) && $data['bootstrap'] === 'Successful') {
                return true;
            }
            // Some formats may differ slightly; be tolerant.
            foreach ($data as $k => $v) {
                if ($k === 'bootstrap' && $v === 'Successful') {
                    return true;
                }
            }
        }

        // Fallback heuristics (should rarely be needed)
        if (is_file($this->webRoot . '/sites/default/settings.php') &&
            is_dir($this->webRoot . '/sites/default/files') &&
            is_file($this->webRoot . '/core/lib/Drupal.php')) {
            // Try a light status check
            $out = array();
            $code = 0;
            exec($this->drushCmd('status --fields=drupal-version'), $out, $code);
            if ($code === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Path to drush binary.
     *
     * @return string
     * @throws \RuntimeException
     */
    private function drushBin()
    {
        $bin = $this->repoRoot . '/vendor/drush/drush/drush';
        if (!is_file($bin)) {
            throw new \RuntimeException("Could not locate drush binary at {$bin}. Did you run composer install?");
        }
        return $bin;
    }

    /**
     * Build a drush command that targets the SUT.
     *
     * @param string $args
     * @return string
     */
    private function drushCmd($args)
    {
        $bin  = escapeshellarg($this->drushBin());
        $root = escapeshellarg($this->webRoot);
        return $bin . ' --no-interaction -r ' . $root . ' ' . $args;
    }

    /**
     * Run a drush command and capture stdout (no exception on non-zero exit).
     *
     * @param string $args
     * @return string
     */
    private function drushCapture($args)
    {
        $cmd = $this->drushCmd($args);
        $out = array();
        $code = 0;
        exec($cmd, $out, $code);
        return trim(implode("\n", $out));
    }

    /**
     * Run a shell command and require success.
     *
     * @param string $cmd
     * @param string $message
     * @throws \RuntimeException
     */
    private function execMust($cmd, $message)
    {
        $out = array();
        $code = 0;
        exec($cmd, $out, $code);
        if ($code !== 0) {
            throw new \RuntimeException($message . ":\n" . $cmd . "\n" . implode("\n", $out));
        }
    }
}
