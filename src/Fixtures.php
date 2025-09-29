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
        // If we’ve already installed and it still bootstraps, we’re done.
        if (is_file($this->sentinelPath()) && $this->drupalIsInstalledAndBootstrapped()) {
            return;
        }

        // If already installed (DB present & bootstrap OK), just warm things.
        if ($this->drupalIsInstalledAndBootstrapped()) {
            // Only generate proxies when DB connection is defined.
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

        // Now that Drupal is installed and has a DB, generate proxies.
        $this->generateProxiesIfMissing();

        // Rebuild caches to finalize container & discovery.
        $this->execMust($this->drushCmd('cr'), 'drush cr failed');

        if (!$this->drupalIsInstalledAndBootstrapped()) {
            throw new \RuntimeException('Drupal still did not bootstrap after install.');
        }
        @file_put_contents($this->sentinelPath(), (string) time());
    }

    /** Build a consistent Drush command with explicit root. */
    private function drushCmd(string $args): string
    {
        $bin  = escapeshellarg($this->drush);
        $root = escapeshellarg($this->webRoot);
        return "{$bin} --no-interaction -r {$root} {$args}";
    }

    /**
     * Robust check that Drupal is installed *and* can fully bootstrap.
     * Plain `drush status` can return 0 before DB is connected; we verify:
     *  - settings.php exists and has a DB array,
     *  - drush status reports a successful bootstrap (JSON),
     *  - and at least one DB field is present.
     */
    private function drupalIsInstalledAndBootstrapped(): bool
    {
        if (!$this->settingsHasDb()) {
            return false;
        }

        [$code, $out] = $this->exec($this->drushCmd('status --format=json'));
        if ($code !== 0) {
            return false;
        }

        $data = json_decode($out, true);
        if (!is_array($data)) {
            return false;
        }

        // Drush 10 returns e.g. "bootstrap": "Successful".
        $bootstrap = $data['bootstrap'] ?? $data['bootstrap-message'] ?? '';
        $okBootstrap = is_string($bootstrap) && stripos($bootstrap, 'success') !== false;

        $hasDb = !empty($data['db-name']) || !empty($data['db-driver']) || !empty($data['database']) || !empty($data['db-status']);

        return $okBootstrap && $hasDb;
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
     * Generate known proxy classes, but **only after** DB exists.
     * This avoids: ConnectionNotDefinedException during kernel boot.
     */
    private function generateProxiesIfMissing(): void
    {
        if (!$this->settingsHasDb()) {
            // Nothing to do yet; will be re-run after install.
            return;
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
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        $proc = proc_open($cmd, $desc, $pipes, $cwd ?: $this->repoRoot, $this->inheritedEnv());
        if (!\is_resource($proc)) {
            return [1, 'Failed to start process'];
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $p) {
            @fclose($p);
        }
        $code = proc_close($proc);
        $out  = trim((string) $stdout . ($stderr ? "\n" . (string) $stderr : ''));
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
