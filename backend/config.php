<?php
declare(strict_types=1);

function env_value(string $name, string $default): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function allowed_origins(): array
{
    $configured = env_value(
        'PORTAL_ALLOWED_ORIGINS',
        'http://localhost,http://127.0.0.1,http://localhost:3000,http://127.0.0.1:3000'
    );
    return array_values(array_filter(array_map('trim', explode(',', $configured))));
}

function database_connection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_value('PORTAL_DB_HOST', '127.0.0.1');
    $database = env_value('PORTAL_DB_NAME', 'portal_kampus');
    $username = env_value('PORTAL_DB_USER', 'root');
    $password = env_value('PORTAL_DB_PASSWORD', '');
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
