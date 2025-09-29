<?php

namespace SiteAudit;

final class Fixtures
{
    /** @var self|null */
    private static $instance;

    // Keep the constructor empty and private. No calls to instance() here.
    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function instance()
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }

    // Optional: help in tests
    public static function reset()
    {
        self::$instance = null;
    }

    /**
     * @return string
     */
    public function dbUrl()
    {
        // Prefer the pipeline-provided URL.
        $env = getenv('UNISH_DB_URL');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        // Fallback for local runs.
        $host = getenv('MYSQL_HOST') ?: '127.0.0.1';
        return sprintf('mysql://root:@%s/testsiteaudittooldatabase', $host);
    }
}
