<?php

namespace SiteAudit;

final class Fixtures
{
    /** @var self|null */
    private static $instance;

    private function __construct() {}

    /** @return self */
    public static function instance()
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }

    /**
     * Called by tests before running Drush commands.
     * - Finds the Drupal root created by scenario installers.
     * - Points Drush at it.
     * - Installs Drupal (once) using UNISH_DB_URL.
     *
     * @param array $options
     * @return self
     */
    public static function createSut(array $options = array())
    {
        if (isset($options['UNISH_DB_URL']) && is_string($options['UNISH_DB_URL'])) {
            putenv('UNISH_DB_URL=' . $options['UNISH_DB_URL']);
        }

        $root = self::findDrupalRoot();
        if (!$root) {
            throw new \RuntimeException('Could not locate a Drupal root (looked for core/lib/Drupal.php). Did the scenario install run?');
        }

        // Make sure subsequent Drush calls (from tests) use this root + a safe URI.
        putenv('DRUSH_OPTIONS_ROOT=' . $root);
        putenv('DRUSH_OPTIONS_URI=http://default');

        // Install if not already bootstrapped.
        if (!self::isBootstrapped($root)) {
            self::installDrupal($root);
        }

        return self::instance();
    }

    /** @return string */
    public function dbUrl()
    {
        $env = getenv('UNISH_DB_URL');
        if (is_string($env) && $env !== '') {
            return $env;
        }
        $host = getenv('MYSQL_HOST') ?: '127.0.0.1';
        return sprintf('mysql://root:@%s/testsiteaudittooldatabase', $host);
    }

    /** Reset between tests if needed. */
    public static function reset()
    {
        self::$instance = null;
    }

    // -------------------- internals --------------------

    /** @return string|null Absolute Drupal root or null. */
    private static function findDrupalRoot()
    {
        $candidates = array(
            'web',
            'docroot',
            'html',
            'drupal/web',
            'drupal',
            '.build/web',
        );
        foreach ($candidates as $rel) {
            $path = self::join(getcwd(), $rel);
            if (is_file(self::join($path, 'core/lib/Drupal.php'))) {
                return realpath($path);
            }
        }
        // Last resort: shallow scan a couple of levels for core/lib/Drupal.php.
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(getcwd(), \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($rii as $file) {
            if ($file->getFilename() === 'Drupal.php' && strpos($file->getPathname(), 'core/lib/Drupal.php') !== false) {
                return realpath(dirname(dirname($file->getPathname()))); // up to <root>/core
            }
        }
        return null;
    }

    /** @param string $root */
    private static function isBootstrapped($root)
    {
        $out = self::drush($root, 'status --field=bootstrap', true);
        // Drush 8/9/10 print "Successful" when bootstrapped.
        return stripos(trim($out), 'successful') !== false;
    }

    /** @param string $root */
    private static function installDrupal($root)
    {
        // Ensure sites/default is writable so Drush can write settings.php/files.
        @chmod(self::join($root, 'sites', 'default'), 0777);
        @chmod(self::join($root, 'sites', 'default', 'settings.php'), 0666);

        $dbUrl = getenv('UNISH_DB_URL');
        if (!$dbUrl) {
            $dbUrl = self::instance()->dbUrl();
            putenv('UNISH_DB_URL=' . $dbUrl);
        }

        // Use the short alias "si" to work across Drush 8–11.
        $cmd = sprintf(
            'si -y --db-url=%s --account-name=admin --account-pass=admin --site-name=SUT standard',
            escapeshellarg($dbUrl)
        );
        self::drush($root, $cmd, false);

        // Sanity: confirm bootstrap after install.
        if (!self::isBootstrapped($root)) {
            throw new \RuntimeException('Drupal install appears to have failed; Drush cannot bootstrap.');
        }
    }

    /**
     * Run drush with this repo’s executable, bound to a root.
     *
     * @param string $root
     * @param string $args
     * @param bool   $quiet  If true, don’t throw on failure (used for probes).
     * @return string Combined stdout (trimmed).
     */
    private static function drush($root, $args, $quiet)
    {
        $bin = self::drushBin();
        $cmd = escapeshellarg($bin) . ' --no-interaction -r ' . escapeshellarg($root) . ' ' . $args . ' 2>&1';
        exec($cmd, $lines, $code);
        $out = trim(implode("\n", $lines));
        if ($code !== 0 && !$quiet) {
            throw new \RuntimeException("Drush failed (exit $code):\n$cmd\n\n$out\n");
        }
        return $out;
    }

    /** @return string */
    private static function drushBin()
    {
        $a = self::join(dirname(__DIR__), 'vendor', 'drush', 'drush', 'drush');
        if (is_file($a) && is_executable($a)) {
            return $a;
        }
        $b = self::join(getcwd(), 'vendor', 'bin', 'drush'); // fallback
        return $b;
    }

    /** @return string */
    private static function join()
    {
        $parts = func_get_args();
        return preg_replace('#/+#','/', join('/', $parts));
    }
}
