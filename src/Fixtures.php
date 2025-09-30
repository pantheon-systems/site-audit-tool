<?php
/**
 * Minimal, PHP 5.6–compatible fixtures helper used by tests.
 *
 * Responsibilities:
 * - Provide Fixtures::instance() singleton (used by tests).
 * - Provide Fixtures::createSut() entrypoint for CI step.
 * - Ensure Drupal is installed and bootstrapped against UNISH_DB_URL.
 * - Provide ->drush($cmd) helper to run Drush commands from tests.
 *
 * Notes:
 * - No typed properties / return types (keeps PHP 5.6 compatible).
 * - No proxy generation here (avoids DB/bootstrap recursion).
 */

namespace SiteAudit;

class Fixtures
{
    /** @var Fixtures|null */
    private static $instance = null;

    /** @var string Absolute path to the repo root. */
    private $projectRoot;

    /** @var string Absolute path to Drupal root (sut/web). */
    private $drupalRoot;

    /** @var string Absolute path to the drush executable. */
    private $drushBin;

    private function __construct()
    {
        $workspace = getenv('GITHUB_WORKSPACE');
        if (!$workspace) {
            $workspace = getcwd();
        }
        $this->projectRoot = rtrim($workspace, '/');

        $root = getenv('DRUSH_OPTIONS_ROOT');
        if (!$root) {
            $root = $this->projectRoot . '/sut/web';
        }
        $this->drupalRoot = rtrim($root, '/');

        $this->drushBin = $this->projectRoot . '/vendor/drush/drush/drush';
    }

    /**
     * Singleton accessor used by tests.
     *
     * @return Fixtures
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * CI entrypoint: ensure SUT exists and Drupal is installed.
     */
    public static function createSut()
    {
        self::instance()->ensureSutInstalled();
    }

    /**
     * Path to Drupal root.
     *
     * @return string
     */
    public function root()
    {
        return $this->drupalRoot;
    }

    /**
     * Run a Drush command with our standard environment.
     *
     * Example: $this->drush('status');
     * $this->drush('pm:enable views -y');
     *
     * @param string|array $cmd
     * @param bool $mustSucceed
     * @return array [exitCode, stdout, stderr]
     */
    public function drush($cmd, $mustSucceed = true)
    {
        if (is_array($cmd)) {
            $cmd = implode(' ', $cmd);
        }
        $full = escapeshellcmd($this->drushBin) . ' --no-interaction ' . $cmd;
        return $this->exec($full, $mustSucceed, $this->projectRoot, $this->drushEnv());
    }

    /**
     * Ensure Drupal exists and is bootstrapped. Install if needed.
     */
    private function ensureSutInstalled()
    {
        // Sanity: Drupal root must exist (created by scenario/scaffold step).
        if (!is_dir($this->drupalRoot) || !is_dir($this->drupalRoot . '/core')) {
            throw new \RuntimeException("Expected Drupal root at {$this->drupalRoot}. Run the scenario install step that provisions 'sut/web' before tests.");
        }

        // Ensure settings.php exists (Drush sometimes needs it present).
        $sitesDefault = $this->drupalRoot . '/sites/default';
        $settingsPhp  = $sitesDefault . '/settings.php';
        $defaultPhp   = $sitesDefault . '/default.settings.php';
        if (!file_exists($settingsPhp) && file_exists($defaultPhp)) {
            if (!@copy($defaultPhp, $settingsPhp)) {
                throw new \RuntimeException("Unable to create settings.php in {$sitesDefault}");
            }
            @chmod($settingsPhp, 0644);
        }

        // Are we already bootstrapped?
        $ok = $this->isBootstrapped();
        if (!$ok) {
            // Install Drupal (standard profile) using UNISH_DB_URL.
            $dbUrl = getenv('UNISH_DB_URL');
            if (!$dbUrl) {
                // Fallback matches our GH Actions job defaults.
                $dbUrl = 'mysql://root:root@mysql:3306/testsiteaudittooldatabase';
            }

            // Best-effort create DB (ignore failures; 'si' can still proceed if DB exists).
            $this->drush('sql:create -y --extra=--skip-ssl --db-url=' . $this->escArg($dbUrl), false);

            // Site install.
            $si = 'si -y --extra-db=--skip-ssl --db-url=' . $this->escArg($dbUrl)
                . ' --account-name=admin --account-pass=admin'
                . ' --site-name=' . $this->escArg('SUT')
                . ' standard';
            $this->drush($si, true);

            // Re-check bootstrap.
            if (!$this->isBootstrapped()) {
                throw new \RuntimeException('Drupal still did not bootstrap after install.');
            }
        }
    }

    /**
     * Return true if "drush status" reports bootstrap Successful.
     *
     * @return bool
     */
    private function isBootstrapped()
    {
        $res = $this->drush('status --fields=bootstrap --format=json', false);
        if ($res[0] !== 0) {
            return false;
        }
        $json = json_decode($res[1], true);
        if (!is_array($json)) {
            return false;
        }
        // Drush >=10: {"bootstrap":"Successful"}
        // Drush 8 sometimes prints keys differently; be tolerant.
        $val = null;
        if (isset($json['bootstrap'])) {
            $val = $json['bootstrap'];
        } elseif (isset($json['Bootstrap'])) {
            $val = $json['Bootstrap'];
        }
        return is_string($val) && stripos($val, 'Successful') !== false;
    }

    /**
     * Build a small, clean env for Drush.
     *
     * @return array
     */
    private function drushEnv()
    {
        $env = array();

        // Preserve PATH and HOME from the container.
        foreach (array('PATH', 'HOME') as $k) {
            $v = getenv($k);
            if ($v !== false) {
                $env[$k] = $v;
            }
        }

        // Drush expects these.
        $env['DRUSH_OPTIONS_ROOT'] = $this->drupalRoot;
        $env['DRUSH_OPTIONS_URI']  = getenv('DRUSH_OPTIONS_URI') ? getenv('DRUSH_OPTIONS_URI') : 'http://default';

        // Database URL for install/commands that rely on it.
        $db = getenv('UNISH_DB_URL');
        if ($db !== false && $db !== null && $db !== '') {
            $env['UNISH_DB_URL'] = $db;
        }

        return $env;
    }

    /**
     * Execute a shell command with optional strict failure.
     *
     * @param string      $command
     * @param bool        $mustSucceed
     * @param string|null $cwd
     * @param array|null  $extraEnv
     * @return array [exitCode, stdout, stderr]
     */
    private function exec($command, $mustSucceed = true, $cwd = null, $extraEnv = null)
    {
        $descriptors = array(
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );

        $envPairs = array();
        if (is_array($extraEnv)) {
            foreach ($extraEnv as $k => $v) {
                $envPairs[] = $k . '=' . $v;
            }
        }

        $proc = proc_open($command, $descriptors, $pipes, $cwd ? $cwd : $this->projectRoot, $envPairs);
        if (!is_resource($proc)) {
            throw new \RuntimeException("Failed to start process: " . $command);
        }

        $stdout = '';
        $stderr = '';

        if (isset($pipes[1]) && is_resource($pipes[1])) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }
        if (isset($pipes[2]) && is_resource($pipes[2])) {
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }

        $code = proc_close($proc);

        if ($mustSucceed && $code !== 0) {
            $msg  = "Unexpected exit code {$code} for command:\n{$command}\n\n";
            $msg .= "Working directory: " . ($cwd ? $cwd : $this->projectRoot) . "\n\n";
            $msg .= "Output:\n================\n" . $stdout . "\n\n";
            $msg .= "Error Output:\n================\n" . $stderr . "\n";
            throw new \RuntimeException($msg);
        }

        return array($code, $stdout, $stderr);
    }

    /**
     * Shell-escape a single argument safely.
     *
     * @param string $s
     * @return string
     */
    private function escArg($s)
    {
        return escapeshellarg($s);
    }
}
