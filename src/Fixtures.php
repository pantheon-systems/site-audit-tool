private static function dbUrl(): string {
    $url = getenv('UNISH_DB_URL');
    if ($url) {
        return $url;
    }

    // Fallbacks if env var isn't set (keep your defaults here)
    $host = getenv('MYSQL_HOST') ?: 'mysql';
    $db   = getenv('MYSQL_DATABASE') ?: 'testsiteaudittooldatabase';
    $user = getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('MYSQL_PASSWORD') ?: '';

    // Build mysql://user[:pass]@host/db
    return sprintf(
        'mysql://%s%s@%s/%s',
        $user,
        $pass !== '' ? ':' . $pass : '',
        $host,
        $db
    );
}
