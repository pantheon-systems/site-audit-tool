<?php

namespace SiteAudit;

final class Fixtures
{
    /** @var self|null */
    private static $singleton = null;

    private function __construct() {}

    /**
     * Required by FixturesTrait: return a shared instance.
     */
    public static function instance()
    {
        if (!self::$singleton) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }

    /**
     * Let instance calls transparently proxy to static methods (e.g. $fx->dbUrl()).
     */
    public function __call($name, array $args)
    {
        if (is_callable([self::class, $name])) {
            return forward_static_call([self::class, $name], ...$args);
        }
        throw new \BadMethodCallException("Unknown method: {$name}");
    }

    /**
     * Your existing method that computes the DB URL.
     * (Keep this exactly as you already have it.)
     */
    public static function dbUrl(): string
    {
        $url = getenv('UNISH_DB_URL');
        if (!empty($url)) {
            return $url;
        }

        $host = getenv('MYSQL_HOST') ?: 'mysql';
        $db   = getenv('MYSQL_DATABASE') ?: 'testsiteaudittooldatabase';
        $user = getenv('MYSQL_USER') ?: 'root';
        $pass = getenv('MYSQL_PASSWORD') ?: '';

        return sprintf(
            'mysql://%s%s@%s/%s',
            $user,
            $pass !== '' ? ':' . $pass : '',
            $host,
            $db
        );
    }
}
