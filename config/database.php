<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $path = __DIR__ . '/database.local.php';
    if (!is_file($path)) {
        throw new RuntimeException('Falta config/database.local.php. Copia database.local.example.php y configuralo.');
    }
    $c = require $path;
    foreach (['host','port','dbname','user','password'] as $key) {
        if (!isset($c[$key]) || !is_string($c[$key])) throw new RuntimeException('Configuracion de base de datos incompleta.');
    }
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $c['host'], $c['port'], $c['dbname']);
    $pdo = new PDO($dsn, $c['user'], $c['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
