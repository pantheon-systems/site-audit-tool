<?php

namespace SiteAudit;

final class Fixtures
{
    /** @var self|null */
    private static $instance;

    private function __construct() {}

    /**
     * Singleton accessor used by tests/traits.
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

    /**
     * Historical entry point expected by tests (SUT = “system under test”).
     * Keep the signature loose so tests can pass options if they want.
     *
     * @param array $options
     * @return self
     */
    public static function createSut(array $options = [])
    {
        // Allow tests to override env if they pass it via $options
        if (isset($options['UNISH_DB_URL']) && is_string($options['UNISH_DB_URL'])) {
            putenv('UNISH_DB_URL=' . $options['UNISH_DB_URL']);
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

    /** Handy for tests that need a clean slate */
    public static function reset()
    {
        self::$instance = null;
    }
}
