<?php

namespace SiteAudit;

class Fixtures
{
    /** @var Fixtures|null */
    private static $instance = null;

    /** @var string */
    private $sutRoot;

    /** @var string */
    private $dbUrl;

    /** @var string */
    private $drushBin;

    private function __construct($options = array())
    {
        $this->sutRoot = isset($options['root']) && $options['root']
            ? $options['root']
            : self::defaultRoot();

        $this->dbUrl   = self::getDbUrl();
        $this->drushBin = self::findDrush();
    }

    /**
     * Singleton accessor used by some ad-hoc checks in CI.
     * Example: $f = Fixtures::instance(); echo $f->dbUrl();
     *
     * @param array $options
     * @return Fixtures
     */
    public static function instance($options = array())
    {
        if (self::$instance === null) {
            self::$instance = new self($options);
        }
        return self::$instance;
    }

    /**
     * Idempotently prepare a Drupal SUT and return its root.
     * This is what your pipeline calls before running tests.
     *
     * @param array $options
     * @return string
     * @throws \RuntimeException
     */
    public static function createSut($options = array())
    {
        $fx = self::instance($options);
        $fx->ensureSutInstalled();
        return $fx->sutRoot;
    }

    /**
     * Instance-style getter used in your sanity checks.
     *
     * @return string
     */
    public function dbUrl()
    {
        return $this->dbUrl;
    }

    /**
     * Convenience for tests that want the SUT root.
     *
     * @return string
     */
    public static function sutRoot()
    {
        return self::instance()->sutRoot;
    }

    /**
     * Run a Drush command against a given root.
     * Signature matches what your stack traces showed.
     *
     * @param string $root
     * @param string $args
     * @param bool   $quiet
     * @return string
     * @throws \RuntimeException
     */
    public static function drush($root, $args, $quiet = false)
    {
        $fx  = self::instance();
        $bin = $fx->drushBin;

        $cmd = escapeshellcmd($bin) . ' --no-interaction -r ' . escapeshellarg($root) . ' ' . $args;
        return self::runOrThrow($cmd, 'Drush failed', $quiet);
    }

    /**
     * Perform a minimal site install (idempotent).
     *
     * @param string $root
     * @throws \RuntimeException
     */
    public static function installDrupal($root)
    {
        $fx = self::instance();
        $fx->precreateDatabase(); // avoids TLS/SSL errors from mysql client during drop/create

        self::drush(
            $root,
            'si -y --db-url=' . escapeshellarg($fx->dbUrl) .
            ' --account-name=admin --account-pass=admin --site-name=SUT standard'
        );

        // Make sure caches are warm.
        self::drush($root, 'cr -y', true);
    }

    // -----------------------
    // Internals
    // -----------------------

    private function ensureSutInstalled()
    {
        if (!is_dir($this->sutRoot)) {
            throw new \RuntimeException(
                "Expected Drupal root at {$this->sutRoot}. " .
                "Run the scenario install step that provisions 'sut/web' before tests."
            );
        }

        // Already installed?
        $settings = $this->sutRoot . '/sites/default/settings.php';
        if (!file_exists($settings)) {
            self::installDrupal($this->sutRoot);
        }

        // Ensure Drush knows where the site is during PHPUnit.
        $this->exportDrushEnv();
    }

    private function exportDrushEnv()
    {
        // Unconditionally set the environment variables for the PHP process.
        // This ensures Drush, when called from PHPUnit's DrushTestTrait,
        // can always find the Drupal site.
        $this->putEnvBoth('DRUSH_OPTIONS_ROOT', $this->sutRoot);
        $this->putEnvBoth('DRUSH_OPTIONS_URI', 'http://default');
    }

    private static function defaultRoot()
    {
        $envRoot = getenv('DRUSH_OPTIONS_ROOT');
        if ($envRoot) {
            return $envRoot;
        }
        // Repository root is one level up from /src
        return dirname(__DIR__) . '/sut/web';
    }

    private static function getDbUrl()
    {
        $url = getenv('UNISH_DB_URL');
        if (!$url || $url === '') {
            // Reasonable default for local runs; CI sets UNISH_DB_URL.
            $url = 'mysql://root:@127.0.0.1/testsiteaudittooldatabase';
        }
        return $url;
    }

    private static function findDrush()
    {
        // Prefer CWD vendor, fall back to repo vendor.
        $root = getcwd();
        $bin  = $root . '/vendor/drush/drush/drush';
        if (!is_file($bin)) {
            $bin = dirname(__DIR__) . '/vendor/drush/drush/drush';
        }
        if (!is_file($bin)) {
            throw new \RuntimeException('Unable to locate Drush binary (vendor/drush/drush/drush).');
        }
        return $bin;
    }

    private function precreateDatabase()
    {
        $parts = $this->parseDbUrl($this->dbUrl);
        if (!$parts || !isset($parts['database'])) {
            return;
        }

        $host = isset($parts['host']) ? $parts['host'] : '127.0.0.1';
        $user = isset($parts['user']) ? $parts['user'] : 'root';
        $pass = isset($parts['pass']) ? $parts['pass'] : '';
        $db   = $parts['database'];

        // Prefer mariadb client if present; otherwise mysql.
        $client = 'mysql';
        $exit = self::sh('command -v mariadb >/dev/null 2>&1', null, true);
        if ($exit === 0) {
            $client = 'mariadb';
        }

        $passArg = ($pass !== '') ? " -p'" . str_replace("'", "'\"'\"'", $pass) . "'" : '';
        // --skip-ssl avoids the self-signed chain error observed in CI
        $cmd = $client
            . ' --protocol=tcp -h ' . escapeshellarg($host)
            . ' -u' . escapeshellarg($user)
            . $passArg
            . ' --skip-ssl -e "CREATE DATABASE IF NOT EXISTS `'
            . addslashes($db) . '`;"';

        // Best-effort; ignore failures (Drush will proceed if DB already exists).
        self::sh($cmd, null, true);
    }

    private function parseDbUrl($url)
    {
        $p = parse_url($url);
        if (!$p) {
            return null;
        }
        $p['database'] = isset($p['path']) ? ltrim($p['path'], '/') : null;
        if (!isset($p['host']) || $p['host'] === '') {
            $p['host'] = '127.0.0.1';
        }
        if (!isset($p['user'])) {
            $p['user'] = 'root';
        }
        if (!isset($p['pass'])) {
            $p['pass'] = '';
        }
        return $p;
    }

    private static function runOrThrow($cmd, $label, $quiet)
    {
        $out = array();
        $ret = 0;
        exec($cmd . ' 2>&1', $out, $ret);
        if ($ret !== 0) {
            throw new \RuntimeException(
                $label . ' (exit ' . $ret . "):\n" . $cmd . "\n\n" . implode("\n", $out)
            );
        }
        if (!$quiet) {
            return implode("\n", $out);
        }
        return '';
    }

    /**
     * Shell helper that just returns the exit code (optionally quiet).
     *
     * @param string      $cmd
     * @param string|null $cwd
     * @param bool        $quiet
     * @return int
     */
    private static function sh($cmd, $cwd = null, $quiet = false)
    {
        $spec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $proc = proc_open($cmd, $spec, $pipes, $cwd ? $cwd : null);
        if (!is_resource($proc)) {
            return 1;
        }
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if (!$quiet && strlen($stdout)) {
            echo $stdout, "\n";
        }
        if (!$quiet && strlen($stderr)) {
            // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
            @fwrite(STDERR, $stderr . "\n");
        }
        return $code;
    }

    private function putEnvBoth($k, $v)
    {
        putenv($k . '=' . $v);
        $_ENV[$k]    = $v;
        $_SERVER[$k] = $v;
    }
}
