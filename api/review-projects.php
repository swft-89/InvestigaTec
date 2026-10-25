<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para revisar artículos.']);
$pdo = db();
if ($user['type'] === 'DOCENTE') {
    $roleQuery = $pdo->prepare(
        "SELECT 1 FROM usuarios
         WHERE id_usuario = :id AND tipo_usuario = 'DOCENTE'
           AND ext_aprobada IS TRUE AND estado_cuenta = 'ACTIVA'"
    );
    $roleQuery->execute(['id' => $user['id']]);
    if (!$roleQuery->fetchColumn()) json_response(403, ['error' => 'La cuenta docente no está activa.']);
} elseif (!empty($user['is_admin'])) {
    require __DIR__ . '/../includes/admin.php';
    require_admin($pdo);
} else {
    json_response(403, ['error' => 'Esta sección está disponible para docentes y administradores.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(405, ['error' => 'Método no permitido.']);
}

try {
    $query = $pdo->query(
        "SELECT p.id_proyecto, p.titulo, p.estado, p.fecha_creacion,
                u.correo AS creador_correo,
                (SELECT COUNT(*) FROM ext_reporte_proyecto r
                 WHERE r.id_proyecto = p.id_proyecto AND r.estado <> 'RESUELTO') AS reportes_abiertos
         FROM proyectos p
         JOIN usuarios u ON u.id_usuario = p.creado_por
         WHERE p.estado <> 'PUBLICADO'
         ORDER BY CASE p.estado WHEN 'EN_REVISION' THEN 0 WHEN 'REQUIERE_CAMBIOS' THEN 1 ELSE 2 END,
                  p.fecha_creacion DESC"
    );
    $projects = $query->fetchAll();
    foreach ($projects as &$project) $project['reportes_abiertos'] = (int)$project['reportes_abiertos'];
    unset($project);
    json_response(200, ['projects' => $projects]);
} catch (Throwable $error) {
    error_log('InvestigaTec revisión de artículos: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudieron cargar los artículos para revisión.']);
}
