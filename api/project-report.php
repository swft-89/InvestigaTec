<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para reportar un artículo.']);
$pdo = db();
if ($user['type'] === 'DOCENTE') {
    $roleQuery = $pdo->prepare(
        "SELECT 1 FROM usuarios
         WHERE id_usuario = :id AND tipo_usuario = 'DOCENTE'
           AND ext_aprobada IS TRUE AND estado_cuenta = 'ACTIVA'"
    );
    $roleQuery->execute(['id' => $user['id']]);
    if (!$roleQuery->fetchColumn()) json_response(403, ['error' => 'Solo una cuenta docente activa puede reportar artículos.']);
} elseif (!empty($user['is_admin'])) {
    require __DIR__ . '/../includes/admin.php';
    require_admin($pdo);
} else {
    json_response(403, ['error' => 'Solo un docente o administrador puede reportar artículos.']);
}
post_only();
require_csrf();
$input = json_input();
$projectId = filter_var($input['project_id'] ?? null, FILTER_VALIDATE_INT);
$reason = $input['reason'] ?? null;
if (!$projectId || $projectId < 1 || !is_string($reason) ||
    mb_strlen(trim($reason)) < 10 || mb_strlen(trim($reason)) > 3000) {
    json_response(422, ['error' => 'Indica un motivo válido de entre 10 y 3,000 caracteres.']);
}
$reason = trim($reason);

try {
    $pdo->beginTransaction();
    $projectQuery = $pdo->prepare(
        'SELECT id_proyecto, titulo, estado FROM proyectos WHERE id_proyecto = :id FOR UPDATE'
    );
    $projectQuery->execute(['id' => $projectId]);
    $project = $projectQuery->fetch();
    if (!$project) {
        $pdo->rollBack();
        json_response(404, ['error' => 'No se encontró el artículo.']);
    }
    if ($project['estado'] === 'PUBLICADO' && empty($user['is_admin'])) {
        $pdo->rollBack();
        json_response(409, ['error' => 'Solo se pueden reportar artículos antes de su publicación.']);
    }
    $existingReport = $pdo->prepare(
        "SELECT 1 FROM ext_reporte_proyecto
         WHERE id_proyecto = :proyecto AND id_reportado_por = :usuario AND estado <> 'RESUELTO'"
    );
    $existingReport->execute(['proyecto' => $projectId, 'usuario' => $user['id']]);
    if ($existingReport->fetchColumn()) {
        $pdo->rollBack();
        json_response(409, ['error' => 'Ya existe un reporte pendiente tuyo para este artículo.']);
    }
    $report = $pdo->prepare(
        'INSERT INTO ext_reporte_proyecto (id_proyecto, id_reportado_por, motivo)
         VALUES (:proyecto, :usuario, :motivo)'
    );
    $report->execute(['proyecto' => $projectId, 'usuario' => $user['id'], 'motivo' => $reason]);
    $admins = $pdo->query(
        "SELECT id_usuario FROM usuarios
         WHERE LOWER(correo) = 'admin@investigatec.mx'
           AND ext_administrador IS TRUE AND ext_aprobada IS TRUE
           AND estado_cuenta = 'ACTIVA'"
    )->fetchAll(PDO::FETCH_COLUMN);
    if (!$admins) {
        $pdo->rollBack();
        json_response(503, ['error' => 'No hay una cuenta administradora activa para recibir reportes.']);
    }
    $notify = $pdo->prepare(
        "INSERT INTO ext_notificacion (id_usuario, id_proyecto, tipo, mensaje, estado_envio)
         VALUES (:admin, :proyecto, 'REPORTE_ARTICULO', :mensaje, 'NO_APLICA')"
    );
    $reporterType = !empty($user['is_admin']) ? 'administrador' : 'docente';
    foreach ($admins as $adminId) {
        $notify->execute([
            'admin' => $adminId, 'proyecto' => $projectId,
            'mensaje' => 'Un ' . $reporterType . ' reportó el artículo "' . $project['titulo'] . '". Revisa el caso en Administración.',
        ]);
    }
    $pdo->commit();
    json_response(201, ['message' => 'Reporte enviado al administrador para su revisión.']);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec reportar artículo: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo enviar el reporte.']);
}
