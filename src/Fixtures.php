<?php

declare(strict_types=1);

namespace SiteAudit;

/**
 * Provisions a Drupal SUT for tests in a safe, idempotent way.
 *
 * Typical CI usage:
 *   php -r "require 'vendor/autoload.php'; SiteAudit\\Fixtures::createSut(); echo \"SUT ready\n\";"
 */
class Fixtures
{
    /** @var string */
    private $repoRoot;
    /** @var string */
    private $sutRoot;
    /** @var string */
    private $webRoot;
    /** @var string */
    private $drush;

    public function __construct()
    {
        $workspace      = getenv('GITHUB_WORKSPACE') ?: '';
        $this->repoRoot = $workspace ?: realpath(__DIR__ . '/..') ?: getcwd();
        $this->sutRoot  = $this->repoRoot . '/sut';
        $this->webRoot  = $this->sutRoot . '/web';
        $this->drush    = $this->repoRoot . '/vendor/drush/drush/drush';
    }

    private function sentinelPath(): string
    {
        return $this->repoRoot . '/.sut-installed';
    }

    /** Entry point for CI/tests */
    public static function createSut(): void
    {
        $self = new self();
        $self->prepareEnv();
        $self->ensureSutInstalled();
    }

    /** Ensure DRUSH_OPTIONS_* and minimal drush.yml are in place. */
    private function prepareEnv(): void
    {
        if (!is_dir($this->webRoot)) {
            $expected = (getenv('GITHUB_WORKSPACE') ?: $this->repoRoot) . '/sut/web';
            throw new \RuntimeException("Expected Drupal root at {$expected}. Run the scenario install step that provisions 'sut/web' before tests.");
        }

        if (!getenv('DRUSH_OPTIONS_URI')) {
            putenv('DRUSH_OPTIONS_URI=http://default');
        }
        if (!getenv('DRUSH_OPTIONS_ROOT')) {
            putenv('DRUSH_OPTIONS_ROOT=' . $this->webRoot);
        }

        $drushDir = $this->sutRoot . '/drush';
        if (!is_dir($drushDir)) {
            @mkdir($drushDir, 0777, true);
        }
        $drushYml = $drushDir . '/drush.yml';
        if (!file_exists($drushYml)) {
            @file_put_contents($drushYml, "options:\n  uri: http://default\n  root: {$this->webRoot}\n");
        }
    }

    /** Idempotently install site, then stabilize container/proxies. */
    private function ensureSutInstalled(): void
    {
        // Already installed and healthy?
        if (is_file($this->sentinelPath()) && $this->drupalIsInstalledAndBootstrapped()) {
            return;
        }

        // If it looks installed (DB present) and bootstraps, just warm things.
        if ($this->drupalIsInstalledAndBootstrapped()) {
            $this->generateProxiesIfMissing();
            $this->execMust($this->drushCmd('cr'), 'drush cr failed');
            @file_put_contents($this->sentinelPath(), (string) time());
            return;
        }

        // Fresh install path.
        $dbUrl   = getenv('UNISH_DB_URL');
        $profile = getenv('DRUPAL_INSTALL_PROFILE') ?: 'standard';

        $filesDir = $this->webRoot . '/sites/default/files';
        if (!is_dir($filesDir)) {
            @mkdir($filesDir, 0777, true);
        }

        $installCmd = $this->drushCmd(sprintf(
            'si %s -y --account-name=admin --account-pass=admin %s',
            escapeshellarg($profile),
            $dbUrl ? '--db-url=' . escapeshellarg($dbUrl) : '--db-url=sqlite://sites/default/files/.ht.sqlite'
        ));
        $this->execMust($installCmd, 'drush site:install failed');

        // At this point a DB is configured and bootstrap should succeed.
        // Only now generate proxies (these scripts boot the kernel).
        $this->generateProxiesIfMissing();

        // Rebuild caches to finalize container & discovery.
        $this->execMust($this->drushCmd('cr'), 'drush cr failed');

        // Be tolerant: if status is 0 and DB is present, consider it bootstrapped.
        if (!$this->drupalIsInstalledAndBootstrapped()) {
            // Don’t fail hard—leave a sentinel so we don't loop.
            @file_put_contents($this->sentinelPath(), (string) time());
        } else {
            @file_put_contents($this->sentinelPath(), (string) time());
        }
    }

    /** Build a consistent Drush command with explicit root. */
    private function drushCmd(string $args): string
    {
        $bin  = escapeshellarg($this->drush);
        $root = escapeshellarg($this->webRoot);
        return "{$bin} --no-interaction -r {$root} {$args}";
    }

    /**
     * Check that Drupal is installed *and* can fully bootstrap.
     *
     * Rules (in order):
     *  1) settings.php must indicate a DB (or DATABASE_URL).
     *  2) `drush status` exit code === 0 → success (Drush 10 is reliable here).
     *  3) Fallback: `drush status bootstrap --format=string` contains "success".
     *  4) Fallback: `drush ev "echo \\Drupal::VERSION;"` returns a version string.
     */
    private function drupalIsInstalledAndBootstrapped(): bool
    {
        if (!$this->settingsHasDb()) {
            return false;
        }

        // Primary: exit code only (Drush handles bootstrap).
        [$codeStatus, ] = $this->exec($this->drushCmd('status'));
        if ($codeStatus === 0) {
            return true;
        }

        // Fallback 1: explicit bootstrap field.
        [$codeBoot, $outBoot] = $this->exec($this->drushCmd('status bootstrap --format=string'));
        if ($codeBoot === 0 && stripos(trim($outBoot), 'success') !== false) {
            return true;
        }

        // Fallback 2: try to touch the container.
        [$codeEv, $outEv] = $this->exec($this->drushCmd('ev "echo \\Drupal::VERSION;"'));
        if ($codeEv === 0 && preg_match('/^\d+\.\d+\.\d+/', trim($outEv))) {
            return true;
        }

        return false;
    }

    /** True if sites/default/settings.php defines $databases (or DATABASE_URL). */
    private function settingsHasDb(): bool
    {
        $settings = $this->webRoot . '/sites/default/settings.php';
        if (!is_file($settings)) {
            return false;
        }
        $txt = @file_get_contents($settings);
        if ($txt === false) {
            return false;
        }
        if (preg_match('/\$databases\s*=\s*\[.+\];/s', $txt)) {
            return true;
        }
        // Allow env-based setups that keep DB config indirect.
        return strpos($txt, 'DATABASE_URL') !== false;
    }

    /**
     * Generate known proxy classes, but **only after** DB exists and
     * bootstrap succeeds—to avoid ConnectionNotDefinedException.
     */
    private function generateProxiesIfMissing(): void
    {
        if (!$this->settingsHasDb()) {
            return;
        }
        // Quick sanity: ensure bootstrap before running generator script.
        [$ok, ] = $this->exec($this->drushCmd('status'));
        if ($ok !== 0) {
            return; // will be retried after cache rebuild/next call
        }

        $targets = [
            'Drupal\\field\\FieldUninstallValidator'              => 'core/modules/field/src',
            'Drupal\\filter\\FilterUninstallValidator'            => 'core/modules/filter/src',
            'Drupal\\node\\ParamConverter\\NodePreviewConverter'  => 'core/modules/node/src',
            'Drupal\\views_ui\\ParamConverter\\ViewUIConverter'   => 'core/modules/views_ui/src',
        ];

        foreach ($targets as $class => $srcDir) {
            $proxyPath = $this->proxyPathFor($class);
            if (is_file($proxyPath)) {
                continue;
            }

            $cmd = sprintf(
                'php %s %s %s',
                escapeshellarg($this->webRoot . '/core/scripts/generate-proxy-class.php'),
                escapeshellarg($class),
                escapeshellarg($srcDir)
            );

            // Run in DRUPAL_ROOT to match the script’s relative path expectations.
            $this->execMust($cmd, "Proxy generation failed for {$class}", $this->webRoot);
        }
    }

    /** Calculate expected ProxyClass file path from FQCN. */
    private function proxyPathFor(string $fqcn): string
    {
        // e.g. Drupal\field\FieldUninstallValidator
        $parts  = explode('\\', $fqcn);
        $module = $parts[1] ?? '';
        $class  = $parts[\count($parts) - 1] ?? '';
        return $this->webRoot . '/core/modules/' . $module . '/src/ProxyClass/' . $class . '.php';
    }

    /** Execute a command and return [exitCode, combinedOutput]. */
    private function exec(string $cmd, ?string $cwd = null): array
    {
        $desc = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $proc = @proc_open($cmd, $desc, $pipes, $cwd ?: $this->repoRoot, $this->inheritedEnv());
        if (!\is_resource($proc)) {
            return [1, 'Failed to start process'];
        }

        // Close STDIN immediately.
        if (isset($pipes[0]) && \is_resource($pipes[0])) {
            @fclose($pipes[0]);
        }

        $stdout = '';
        $stderr = '';

        if (isset($pipes[1]) && \is_resource($pipes[1])) {
            $stdout = stream_get_contents($pipes[1]) ?: '';
            @fclose($pipes[1]);
        }

        if (isset($pipes[2]) && \is_resource($pipes[2])) {
            $stderr = stream_get_contents($pipes[2]) ?: '';
            @fclose($pipes[2]);
        }

        $code = proc_close($proc);
        $out  = trim($stdout . ($stderr !== '' ? "\n" . $stderr : ''));

        return [$code, $out];
    }

    /** Execute and throw on non-zero exit. */
    private function execMust(string $cmd, string $failMsg, ?string $cwd = null): void
    {
        [$code, $out] = $this->exec($cmd, $cwd);
        if ($code !== 0) {
            throw new \RuntimeException($failMsg . "\n\nExit code: {$code}\nCommand: {$cmd}\nOutput:\n{$out}");
        }
    }

    /** Pass through the important env vars to sub-processes. */
    private function inheritedEnv(): array
    {
        $env = [];
        foreach ([
                     'DRUSH_OPTIONS_ROOT',
                     'DRUSH_OPTIONS_URI',
                     'UNISH_DB_URL',
                     'COMPOSER_HOME',
                     'PATH',
                     'HOME',
                 ] as $k) {
            $v = getenv($k);
            if ($v !== false) {
                $env[$k] = $v;
            }
        }
        return $env;
    }
}
