<?php
declare(strict_types=1);

namespace SiteAudit;

/**
 * Test fixtures helper for spinning up a Drupal SUT (system under test).
 *
 * Responsibilities:
 * - Locate the repo root and SUT root (sut/web).
 * - Read UNISH_DB_URL and expose it via dbUrl().
 * - Pre-create the database with --skip-ssl (works with CI MySQL service).
 * - Perform a Drupal site-install once, then skip on subsequent calls.
 * - Provide a thin Drush wrapper which throws on non-zero exit codes.
 */
final class Fixtures
{
    /** @var Fixtures|null */
    private static $singleton = null;

    /** @var string Absolute path to repo root (directory that has composer.json). */
    private $projectRoot;

    /** @var string Absolute path to Drupal root inside the SUT (sut/web). */
    private $sutRoot;

    /** @var string Database URL (e.g., mysql://root:@mysql/testsiteaudittooldatabase). */
    private $dbUrl;

    private function __construct()
    {
        // project root = one up from this file's directory (…/src)
        $this->projectRoot = (string) realpath(\dirname(__DIR__));
        $this->sutRoot     = $this->projectRoot . '/sut/web';
        $this->dbUrl       = getenv('UNISH_DB_URL') ?: 'mysql://root:@127.0.0.1/testsiteaudittooldatabase';
    }

    /** Singleton accessor used by tests. */
    public static function instance()
    {
        if (!self::$singleton) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }

    /**
     * Ensure a working SUT. Idempotent: if Drupal is already installed and
     * bootstraps successfully, this is a no-op.
     */
    public static function createSut(array $options = [])
    {
        $self = self::instance();

        $self->ensureSutExists();

        if (!$self->isInstalled()) {
            // Prepare DB first; Drush may refuse to create it in CI.
            $self->prepareDatabase();

            // Perform a standard profile install.
            $self->installDrupal();

            // Make sure container/proxies are fresh.
            $self->drush('cr', true);
        }

        return $self;
    }

    /** Absolute path of the repo root (directory with composer.json). */
    public function projectRoot()
    {
        return $this->projectRoot;
    }

    /** Absolute path of the Drupal root inside the SUT (sut/web). */
    public function sutRoot()
    {
        return $this->sutRoot;
    }

    /** The DB URL pulled from UNISH_DB_URL (or default). */
    public function dbUrl()
    {
        return $this->dbUrl;
    }

    /**
     * Run a Drush command against the SUT.
     *
     * @param string $args         e.g. "status" or "pm:enable views_ui -y"
     * @param bool   $quiet        if true, suppresses streaming stdout/stderr
     * @param bool   $throwOnError throw RuntimeException on non-zero exit
     * @return array{0:int,1:string,2:string} [exit, stdout, stderr]
     */
    public function drush($args, $quiet = false, $throwOnError = true)
    {
        $bin = $this->projectRoot . '/vendor/drush/drush/drush';

        $cmd = sprintf(
            "'%s' --no-interaction -r '%s' %s",
            $bin,
            $this->sutRoot,
            $args
        );

        return $this->run($cmd, $this->projectRoot, $quiet, $throwOnError, /*labelForError*/'Drush');
    }

    /* ---------- internals ---------- */

    private function ensureSutExists()
    {
        if (!is_dir($this->sutRoot)) {
            throw new \RuntimeException(
                "SUT not found at {$this->sutRoot}. Ensure your scenario step scaffolded Drupal into 'sut/web'."
            );
        }
    }

    private function isInstalled()
    {
        // Prefer asking Drush; fall back to quick file checks if needed.
        try {
            list($code, $out) = $this->drush('status --field=bootstrap', true, false);
            if ($code === 0 && preg_match('/Successful/i', $out)) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore and fall through to file checks
        }

        $settings = $this->sutRoot . '/sites/default/settings.php';
        return is_file($settings);
    }

    private function installDrupal()
    {
        $args = sprintf(
            "si -y --db-url=%s --account-name=%s --account-pass=%s --site-name=%s standard",
            escapeshellarg($this->dbUrl),
            escapeshellarg('admin'),
            escapeshellarg('admin'),
            escapeshellarg('SUT')
        );

        // If install fails, throw with the exact command echoed (matches test expectations)
        $this->drush($args, false, true);
    }

    private function prepareDatabase()
    {
        $dsn = $this->parseDbUrl($this->dbUrl);

        if (($dsn['scheme'] ?? '') !== 'mysql') {
            return; // Only MySQL/MariaDB supported in CI
        }

        $host = $dsn['host'] ?? '127.0.0.1';
        $port = (int)($dsn['port'] ?? 3306);
        $user = $dsn['user'] ?? 'root';
        $pass = $dsn['pass'] ?? '';
        $db   = ltrim((string)($dsn['path'] ?? ''), '/');

        if ($db === '') {
            return;
        }

        $cli = $this->findMysqlCli();
        $pw  = $pass !== '' ? "-p" . str_replace("'", "'\"'\"'", $pass) : '';

        $sql = sprintf(
            "DROP DATABASE IF EXISTS `%s`; CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;",
            $db,
            $db
        );

        $cmd = sprintf(
            "%s --protocol=tcp -h %s -P %d -u %s %s --skip-ssl -e %s",
            $cli,
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            $pw,
            escapeshellarg($sql)
        );

        // Don't fail the whole run if this step can't manage the DB; Drush may still succeed.
        $this->run($cmd, $this->projectRoot, true, false, 'mysql');
    }

    /** @return array{scheme?:string,user?:string,pass?:string,host?:string,port?:int,path?:string} */
    private function parseDbUrl($url)
    {
        $parts = parse_url($url);
        if ($parts === false) {
            throw new \InvalidArgumentException("Invalid DB URL: {$url}");
        }
        return $parts;
    }

    private function findMysqlCli()
    {
        foreach (['mysql', 'mariadb'] as $bin) {
            $path = trim((string)@shell_exec("command -v {$bin}"));
            if ($path !== '') {
                return escapeshellcmd($path);
            }
        }
        return 'mysql';
    }

    /**
     * Run a shell command and capture exit/stdout/stderr.
     *
     * @return array{0:int,1:string,2:string}
     */
    private function run(
        $cmd,
        $cwd = null,
        $quiet = false,
        $throwOnError = true,
        $labelForError = 'Command'
    ) {
        $spec = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $proc = proc_open($cmd, $spec, $pipes, $cwd ?? getcwd());
        if (!\is_resource($proc)) {
            throw new \RuntimeException("Failed to execute: {$cmd}");
        }

        $out  = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err  = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $code = proc_close($proc);

        if (!$quiet) {
            if ($out !== '') { fwrite(STDOUT, $out); }
            if ($err !== '') { fwrite(STDERR, $err); }
        }

        if ($code !== 0 && $throwOnError) {
            // Match the error format your logs showed (include command and "2>&1" hint).
            $message = sprintf(
                "%s failed (exit %d):\n%s 2>&1\n\n%s",
                $labelForError,
                $code,
                $cmd,
                trim($err) !== '' ? trim($err) : trim($out)
            );
            throw new \RuntimeException($message);
        }

        return [$code, (string)$out, (string)$err];
    }
}
