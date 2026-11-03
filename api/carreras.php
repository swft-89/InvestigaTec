<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(405,['error'=>'Metodo no permitido.']);
try {
    $rows = db()->query('SELECT id_carrera, nombre, siglas FROM carreras WHERE activa IS TRUE ORDER BY nombre')->fetchAll();
    json_response(200,['carreras'=>$rows]);
} catch (Throwable $e) {
    error_log('InvestigaTec carreras: '.$e->getMessage());
    json_response(500,['error'=>'No se pudieron cargar las carreras. Revisa PostgreSQL.']);
}
