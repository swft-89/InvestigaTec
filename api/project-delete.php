<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

post_only();
require_csrf();
$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para eliminar un artículo.']);
$input = json_input();
$projectId = filter_var($input['project_id'] ?? null, FILTER_VALIDATE_INT);
if (!$projectId || $projectId < 1) {
    json_response(422, ['error' => 'El artículo indicado no es válido.']);
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $projectQuery = $pdo->prepare(
        'SELECT estado, eliminado, creado_por FROM proyectos WHERE id_proyecto = :id FOR UPDATE'
    );
    $projectQuery->execute(['id' => $projectId]);
    $project = $projectQuery->fetch();
    if (!$project || filter_var($project['eliminado'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
        $pdo->rollBack();
        json_response(404, ['error' => 'No se encontró el artículo.']);
    }

    $isOwner = (int)$project['creado_por'] === (int)$user['id'];
    if (!$isOwner) {
        $authorQuery = $pdo->prepare(
            "SELECT 1 FROM proyectos_autores
             WHERE id_proyecto = :proyecto AND id_usuario = :usuario
               AND tipo_participacion = 'AUTOR'"
        );
        $authorQuery->execute(['proyecto' => $projectId, 'usuario' => $user['id']]);
        $isOwner = (bool)$authorQuery->fetchColumn();
    }
    if (!$isOwner) {
        $pdo->rollBack();
        json_response(403, ['error' => 'Solo el creador o un autor del artículo puede eliminarlo.']);
    }
    if (!in_array($project['estado'], ['BORRADOR', 'RECHAZADO'], true)) {
        $pdo->rollBack();
        json_response(409, ['error' => 'Solo se pueden eliminar artículos en borrador o rechazados.']);
    }

    $delete = $pdo->prepare(
        'UPDATE proyectos SET eliminado = TRUE WHERE id_proyecto = :id AND eliminado IS FALSE'
    );
    $delete->execute(['id' => $projectId]);
    $pdo->commit();
    json_response(200, ['message' => 'El artículo se marcó como eliminado.']);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec eliminar artículo: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo marcar el artículo como eliminado.']);
}
