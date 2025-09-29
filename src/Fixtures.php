<?php

declare(strict_types=1);

namespace SiteAudit;

/**
 * Test fixtures helper for provisioning and stabilizing the SUT (Drupal site).
 *
 * Usage (from CI or locally):
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

    /** Sentinel to avoid re-install loops. */
    private function sentinelPath(): string
    {
        return $this->repoRoot . '/.sut-installed';
    }

    public function __construct()
    {
        // Prefer CI workspace if provided (keeps paths consistent in logs).
        $workspace = getenv('GITHUB_WORKSPACE');
        $this->repoRoot = $workspace ?: realpath(__DIR__ . '/..') ?: getcwd();
        $this->sutRoot  = $this->repoRoot . '/sut';
        $this->webRoot  = $this->sutRoot . '/web';
        $this->drush    = $this->repoRoot . '/vendor/drush/drush/drush';
    }

    /**
     * Entry point used by CI and tests.
     */
    public static function createSut(): void
    {
        $self = new self();
        $self->prepareEnv();
        $self->ensureSutInstalled();
    }

    /**
     * Ensure DRUSH_OPTIONS_* env is sensible for all spawned processes.
     */
    private function prepareEnv(): void
    {
        if (!is_dir($this->webRoot)) {
            $expected = ($workspace = getenv('GITHUB_WORKSPACE')) ? "$workspace/sut/web" : $this->webRoot;
            throw new \RuntimeException("Expected Drupal root at {$expected}. Run the scenario install step that provisions 'sut/web' before tests.");
        }

        // Default to http://default if not set (Drush convention in tests).
        if (!getenv('DRUSH_OPTIONS_URI')) {
            putenv('DRUSH_OPTIONS_URI=http://default');
        }
        // Always set ROOT explicitly (even if also passed via -r).
        if (!getenv('DRUSH_OPTIONS_ROOT')) {
            putenv('DRUSH_OPTIONS_ROOT=' . $this->webRoot);
        }

        // Minimal drush config so local runs behave consistently.
        $drushDir = $this->sutRoot . '/drush';
        if (!is_dir($drushDir)) {
            @mkdir($drushDir, 0777, true);
        }
        $drushYml = $drushDir . '/drush.yml';
        if (!file_exists($drushYml)) {
            $yml = <<<YML
options:
  uri: http://default
  root: {$this->webRoot}
YML;
            @file_put_contents($drushYml, $yml);
        }
    }

    /**
     * Idempotently ensure the SUT is installed and its container is stable.
     */
    private function ensureSutInstalled(): void
    {
        // If we know we've finished a successful install before, bail early.
        if (is_file($this->sentinelPath()) && $this->drupalBootstraps()) {
            return;
        }

        // If it already bootstraps, just make sure proxies exist and caches are warm.
        if ($this->drupalBootstraps()) {
            $this->generateProxiesIfMissing();
            $this->execMust($this->drushCmd('cr'), 'drush cr failed');
            @file_put_contents($this->sentinelPath(), (string) time());
            return;
        }

        // Fresh install (profile 'standard' unless overridden).
        $dbUrl   = getenv('UNISH_DB_URL');
        $profile = getenv('DRUPAL_INSTALL_PROFILE') ?: 'standard';

        // Ensure public files dir exists; SQLite fallback needs it too.
        $filesDir = $this->webRoot . '/sites/default/files';
        if (!is_dir($filesDir)) {
            @mkdir($filesDir, 0777, true);
        }

        $si = $this->drushCmd(sprintf(
            'si %s -y --account-name=admin --account-pass=admin %s',
            escapeshellarg($profile),
            $dbUrl ? '--db-url=' . escapeshellarg($dbUrl) : '--db-url=sqlite://sites/default/files/.ht.sqlite'
        ));
        $this->execMust($si, 'drush site:install failed');

        // Generate missing ProxyClass files if Composer was dumped with -a.
        $this->generateProxiesIfMissing();

        // Rebuild caches to finalize container and service discovery.
        $this->execMust($this->drushCmd('cr'), 'drush cr failed');

        // Confirm bootstrap once more, then write sentinel.
        if (!$this->drupalBootstraps()) {
            throw new \RuntimeException('Drupal still did not bootstrap after install.');
        }
        @file_put_contents($this->sentinelPath(), (string) time());
    }

    /**
     * Drush command builder with explicit -r to avoid env-only reliance.
     */
    private function drushCmd(string $args): string
    {
        $bin = escapeshellarg($this->drush);
        $root = escapeshellarg($this->webRoot);
        // Always run with --no-interaction and explicit root; let args supply anything else.
        return "{$bin} --no-interaction -r {$root} {$args}";
    }

    /**
     * Does "drush status" return success (site bootstrapped)?
     */
    private function drupalBootstraps(): bool
    {
        [$code,] = $this->exec($this->drushCmd('status --format=json'));
        return $code === 0;
    }

    /**
     * Generate known missing proxy classes when Composer's autoloader is authoritative (-a).
     * Safe to run multiple times; it only creates files that aren't there yet.
     */
    private function generateProxiesIfMissing(): void
    {
        // Class => relative source directory inside core/modules/**/src
        $targets = [
            // Core + contrib complaints commonly seen on clean installs (D9.x).
            'Drupal\\field\\FieldUninstallValidator'           => 'core/modules/field/src',
            'Drupal\\filter\\FilterUninstallValidator'         => 'core/modules/filter/src',
            'Drupal\\node\\ParamConverter\\NodePreviewConverter' => 'core/modules/node/src',
            'Drupal\\views_ui\\ParamConverter\\ViewUIConverter'  => 'core/modules/views_ui/src',
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
            // Run from web root so relative paths in the script are correct.
            $this->execMust($cmd, "Proxy generation failed for {$class}", $this->webRoot);
        }
    }

    /**
     * Calculate ProxyClass file path for a given FQCN.
     */
    private function proxyPathFor(string $fqcn): string
    {
        // Example: Drupal\field\FieldUninstallValidator
        // -> core/modules/field/src/ProxyClass/FieldUninstallValidator.php
        if (strpos($fqcn, 'Drupal\\') !== 0) {
            return $this->webRoot . '/core'; // harmless default
        }
        $parts = explode('\\', $fqcn);               // ['Drupal','field','FieldUninstallValidator']
        $module = $parts[1];                         // 'field'
        $class  = end($parts);                       // 'FieldUninstallValidator'
        return $this->webRoot . '/core/modules/' . $module . '/src/ProxyClass/' . $class . '.php';
    }

    /**
     * Execute a shell command. Returns [exitCode, output].
     */
    private function exec(string $cmd, ?string $cwd = null): array
    {
        $descriptors = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        $proc = proc_open($cmd, $descriptors, $pipes, $cwd ?: $this->repoRoot, $this->inheritedEnv());
        if (!\is_resource($proc)) {
            return [1, 'Failed to start process'];
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $p) {
            @fclose($p);
        }
        $code = proc_close($proc);
        $out = trim($stdout . ($stderr ? "\n" . $stderr : ''));
        return [$code, $out];
    }

    /**
     * Execute a shell command and throw on non-zero exit.
     */
    private function execMust(string $cmd, string $failMsg, ?string $cwd = null): void
    {
        [$code, $out] = $this->exec($cmd, $cwd);
        if ($code !== 0) {
            throw new \RuntimeException($failMsg . "\n\nExit code: {$code}\nCommand: {$cmd}\nOutput:\n" . $out);
        }
    }

    /**
     * Inherit important env (esp. DRUSH_OPTIONS_* and DB URL) for subprocesses.
     */
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
                 ] as $key) {
            $val = getenv($key);
            if ($val !== false) {
                $env[$key] = $val;
            }
        }
        return $env;
    }
}
