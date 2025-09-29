<?php

namespace SiteAudit;

final class Fixtures
{
    /**
     * Build the DB URL from env so CI can point to the mysql service.
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

    /**
     * Example: wherever you call Drush, use self::dbUrl().
     */
    public static function installDrupal(): void
    {
        $cmd = [
            __DIR__ . '/../../vendor/drush/drush/drush',
            '--no-interaction',
            'site-install',
            '--yes',
            "--db-url='" . self::dbUrl() . "'",
        ];
        // however you execute commands now — keep your existing helper
        // e.g. CliTestTrait::execute() or your wrapper
    }
}
