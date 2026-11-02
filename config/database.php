<?php
declare(strict_types=1);

function database_config(): array {
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl !== false && $databaseUrl !== '') {
        $url = parse_url($databaseUrl);
        $scheme = is_array($url) ? strtolower((string)($url['scheme'] ?? '')) : '';
        if (!is_array($url) || !in_array($scheme, ['postgres', 'postgresql'], true)) {
            throw new RuntimeException('DATABASE_URL no es una URL de PostgreSQL valida.');
        }

        foreach (['host', 'user', 'pass', 'path'] as $key) {
            if (!isset($url[$key]) || $url[$key] === '') {
                throw new RuntimeException('DATABASE_URL esta incompleta.');
            }
        }

        $config = [
            'host' => $url['host'],
            'port' => (string)($url['port'] ?? 5432),
            'dbname' => rawurldecode(ltrim($url['path'], '/')),
            'user' => rawurldecode($url['user']),
            'password' => rawurldecode($url['pass']),
        ];

        parse_str($url['query'] ?? '', $query);
        if (isset($query['sslmode'])) {
            $sslModes = ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'];
            if (!in_array($query['sslmode'], $sslModes, true)) {
                throw new RuntimeException('El sslmode de DATABASE_URL no es valido.');
            }
            $config['sslmode'] = $query['sslmode'];
        }

        return $config;
    }

    $path = __DIR__ . '/database.local.php';
    if (!is_file($path)) {
        throw new RuntimeException('Falta config/database.local.php y no se definio DATABASE_URL.');
    }

    return require $path;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $c = database_config();
    foreach (['host','port','dbname','user','password'] as $key) {
        if (!isset($c[$key]) || !is_string($c[$key]) || $c[$key] === '') {
            throw new RuntimeException('Configuracion de base de datos incompleta.');
        }
    }
    foreach (['host','port','dbname'] as $key) {
        if (str_contains($c[$key], ';') || str_contains($c[$key], "\0")) {
            throw new RuntimeException('Configuracion de base de datos incompleta.');
        }
    }
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $c['host'], $c['port'], $c['dbname']);
    if (isset($c['sslmode'])) $dsn .= ';sslmode=' . $c['sslmode'];
    $pdo = new PDO($dsn, $c['user'], $c['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    return $pdo;
}
