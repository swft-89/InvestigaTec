<?php
declare(strict_types=1);
require __DIR__ . '/../includes/admin.php';

$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión con la cuenta administradora.']);
$pdo = db();
$admin = require_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $users = $pdo->query(
            "SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno,
                    u.correo, u.tipo_usuario, u.estado_cuenta, u.ext_aprobada,
                    u.ext_administrador, u.fecha_registro, u.ultimo_acceso,
                    COALESCE(c.nombre, d.nombre, '') AS afiliacion
             FROM usuarios u
             LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
             LEFT JOIN carreras c ON c.id_carrera = e.id_carrera
             LEFT JOIN docentes doc ON doc.id_usuario = u.id_usuario
             LEFT JOIN departamentos d ON d.id_departamento = doc.id_departamento
             ORDER BY u.fecha_registro DESC, u.id_usuario DESC"
        )->fetchAll();
        foreach ($users as &$listedUser) {
            $listedUser['ext_aprobada'] = filter_var($listedUser['ext_aprobada'], FILTER_VALIDATE_BOOLEAN);
            $listedUser['ext_administrador'] = filter_var($listedUser['ext_administrador'], FILTER_VALIDATE_BOOLEAN);
        }
        unset($listedUser);

        $projects = $pdo->query(
            "SELECT p.id_proyecto, p.titulo, p.estado, p.fecha_creacion,
                    u.correo AS creador_correo,
                    (SELECT COUNT(*) FROM ext_reporte_proyecto r
                     WHERE r.id_proyecto = p.id_proyecto AND r.estado <> 'RESUELTO') AS reportes_abiertos
             FROM proyectos p
             JOIN usuarios u ON u.id_usuario = p.creado_por
             ORDER BY CASE WHEN EXISTS (
                         SELECT 1 FROM ext_reporte_proyecto r
                         WHERE r.id_proyecto = p.id_proyecto AND r.estado <> 'RESUELTO'
                     ) THEN 0 ELSE 1 END,
                     p.fecha_creacion DESC"
        )->fetchAll();
        foreach ($projects as &$project) $project['reportes_abiertos'] = (int)$project['reportes_abiertos'];
        unset($project);

        $reports = $pdo->query(
            "SELECT r.id_reporte, r.id_proyecto, r.motivo, r.estado, r.fecha_creacion,
                    p.titulo AS proyecto_titulo,
                    trim(concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno)) AS reportado_por,
                    u.correo AS correo_reportante
             FROM ext_reporte_proyecto r
             JOIN proyectos p ON p.id_proyecto = r.id_proyecto
             JOIN usuarios u ON u.id_usuario = r.id_reportado_por
             ORDER BY CASE r.estado WHEN 'PENDIENTE' THEN 0 WHEN 'EN_REVISION' THEN 1 ELSE 2 END,
                      r.fecha_creacion DESC"
        )->fetchAll();

        $requests = $pdo->query(
            "SELECT s.id_solicitud, s.id_usuario, s.tipo, s.nombre_contacto,
                    s.correo_contacto, s.asunto, s.mensaje, s.estado,
                    s.respuesta, s.fecha_creacion,
                    trim(concat_ws(' ', r.nombre, r.apellido_paterno, r.apellido_materno)) AS respondido_por
             FROM ext_ayuda_sugerencias s
             LEFT JOIN usuarios r ON r.id_usuario = s.respuesta_por
             ORDER BY CASE s.estado WHEN 'PENDIENTE' THEN 0 WHEN 'EN_REVISION' THEN 1 ELSE 2 END,
                      s.fecha_creacion DESC"
        )->fetchAll();

        json_response(200, ['users' => $users, 'projects' => $projects, 'reports' => $reports, 'requests' => $requests]);
    } catch (Throwable $error) {
        error_log('InvestigaTec panel administrativo consulta: ' . $error->getMessage());
        json_response(500, ['error' => 'No se pudieron cargar los datos del panel administrativo.']);
    }
}

post_only();
require_csrf();
$input = json_input();
$action = $input['action'] ?? null;

try {
    if ($action === 'set_user_status') {
        $userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
        $status = $input['status'] ?? null;
        if (!$userId || $userId < 1 || !in_array($status, ['APPROVED', 'SUSPENDED'], true)) {
            json_response(422, ['error' => 'La cuenta o el estado indicado no son válidos.']);
        }
        $targetQuery = $pdo->prepare(
            "SELECT id_usuario, ext_administrador FROM usuarios WHERE id_usuario = :id FOR UPDATE"
        );
        $pdo->beginTransaction();
        $targetQuery->execute(['id' => $userId]);
        $target = $targetQuery->fetch();
        if (!$target) {
            $pdo->rollBack();
            json_response(404, ['error' => 'No se encontró la cuenta.']);
        }
        if (filter_var($target['ext_administrador'], FILTER_VALIDATE_BOOLEAN) || (int)$target['id_usuario'] === (int)$admin['id']) {
            $pdo->rollBack();
            json_response(422, ['error' => 'No puedes cambiar el estado de la cuenta administradora.']);
        }
        if ($status === 'APPROVED') {
            $update = $pdo->prepare("UPDATE usuarios SET ext_aprobada = TRUE, estado_cuenta = 'ACTIVA' WHERE id_usuario = :id");
        } else {
            $update = $pdo->prepare("UPDATE usuarios SET ext_aprobada = FALSE, estado_cuenta = 'BLOQUEADA' WHERE id_usuario = :id");
        }
        $update->execute(['id' => $userId]);
        $pdo->commit();
        json_response(200, ['message' => $status === 'APPROVED' ? 'Cuenta aprobada y activada.' : 'Cuenta suspendida.']);
    }

    if ($action === 'set_project_status') {
        $projectId = filter_var($input['project_id'] ?? null, FILTER_VALIDATE_INT);
        $status = $input['status'] ?? null;
        $allowedStatuses = ['BORRADOR', 'EN_REVISION', 'REQUIERE_CAMBIOS', 'APROBADO', 'RECHAZADO', 'PUBLICADO'];
        if (!$projectId || $projectId < 1 || !is_string($status) || !in_array($status, $allowedStatuses, true)) {
            json_response(422, ['error' => 'El artículo o el estado indicado no son válidos.']);
        }
        $pdo->beginTransaction();
        $currentQuery = $pdo->prepare('SELECT estado FROM proyectos WHERE id_proyecto = :id FOR UPDATE');
        $currentQuery->execute(['id' => $projectId]);
        $previous = $currentQuery->fetchColumn();
        if ($previous === false) {
            $pdo->rollBack();
            json_response(404, ['error' => 'No se encontró el artículo.']);
        }
        if (in_array($status, ['APROBADO', 'PUBLICADO'], true)) {
            $openReports = $pdo->prepare(
                "SELECT COUNT(*) FROM ext_reporte_proyecto
                 WHERE id_proyecto = :id AND estado <> 'RESUELTO'"
            );
            $openReports->execute(['id' => $projectId]);
            if ((int)$openReports->fetchColumn() > 0) {
                $pdo->rollBack();
                json_response(409, ['error' => 'Resuelve los reportes pendientes de este artículo antes de aprobarlo o publicarlo.']);
            }
        }
        if ($previous !== $status) {
            $update = $pdo->prepare(
                "UPDATE proyectos SET estado = CAST(:estado AS estado_proyecto_enum),
                    fecha_publicacion = CASE WHEN :publicado = 'PUBLICADO'
                        THEN COALESCE(fecha_publicacion, NOW()) ELSE fecha_publicacion END
                 WHERE id_proyecto = :id"
            );
            $update->execute(['estado' => $status, 'publicado' => $status, 'id' => $projectId]);
            $history = $pdo->prepare(
                'INSERT INTO historial_estados
                    (id_proyecto, estado_anterior, estado_nuevo, cambiado_por, comentario)
                 VALUES (:proyecto, CAST(:anterior AS estado_proyecto_enum),
                    CAST(:nuevo AS estado_proyecto_enum), :usuario, :comentario)'
            );
            $history->execute([
                'proyecto' => $projectId, 'anterior' => $previous, 'nuevo' => $status,
                'usuario' => $admin['id'], 'comentario' => 'Cambio realizado desde el panel administrativo.',
            ]);
        }
        $pdo->commit();
        json_response(200, ['message' => 'Estado del artículo actualizado.']);
    }

    if ($action === 'set_report_status') {
        $reportId = filter_var($input['report_id'] ?? null, FILTER_VALIDATE_INT);
        $status = $input['status'] ?? null;
        if (!$reportId || $reportId < 1 || !in_array($status, ['EN_REVISION', 'RESUELTO'], true)) {
            json_response(422, ['error' => 'El reporte o el estado indicado no son válidos.']);
        }
        $update = $pdo->prepare('UPDATE ext_reporte_proyecto SET estado = :estado WHERE id_reporte = :id');
        $update->execute(['estado' => $status, 'id' => $reportId]);
        if ($update->rowCount() === 0) json_response(404, ['error' => 'No se encontró el reporte.']);
        json_response(200, ['message' => 'Estado del reporte actualizado.']);
    }

    if ($action === 'reply_support') {
        $requestId = filter_var($input['request_id'] ?? null, FILTER_VALIDATE_INT);
        $reply = $input['reply'] ?? null;
        if (!$requestId || $requestId < 1 || !is_string($reply) ||
            mb_strlen(trim($reply)) < 2 || mb_strlen(trim($reply)) > 5000) {
            json_response(422, ['error' => 'Escribe una respuesta de entre 2 y 5,000 caracteres.']);
        }
        $reply = trim($reply);
        $pdo->beginTransaction();
        $requestQuery = $pdo->prepare(
            'SELECT id_solicitud, id_usuario, asunto FROM ext_ayuda_sugerencias
             WHERE id_solicitud = :id FOR UPDATE'
        );
        $requestQuery->execute(['id' => $requestId]);
        $request = $requestQuery->fetch();
        if (!$request) {
            $pdo->rollBack();
            json_response(404, ['error' => 'No se encontró la solicitud de ayuda.']);
        }
        $update = $pdo->prepare(
            "UPDATE ext_ayuda_sugerencias
             SET respuesta = :respuesta, respuesta_por = :admin, estado = 'RESPONDIDA'
             WHERE id_solicitud = :id"
        );
        $update->execute(['respuesta' => $reply, 'admin' => $admin['id'], 'id' => $requestId]);
        if ($request['id_usuario'] !== null) {
            $notification = $pdo->prepare(
                "INSERT INTO ext_notificacion
                    (id_usuario, id_solicitud_ayuda, tipo, mensaje, estado_envio)
                 VALUES (:usuario, :solicitud, 'RESPUESTA_AYUDA', :mensaje, 'NO_APLICA')"
            );
            $notification->execute([
                'usuario' => $request['id_usuario'], 'solicitud' => $requestId,
                'mensaje' => 'El administrador respondió a tu solicitud "' . $request['asunto'] . '": ' . $reply,
            ]);
        }
        $pdo->commit();
        json_response(200, [
            'message' => $request['id_usuario'] === null
                ? 'Respuesta guardada. Esta solicitud fue enviada como invitado, por lo que no tiene una cuenta asociada para recibir una notificación interna.'
                : 'Respuesta enviada y notificación creada.',
        ]);
    }

    json_response(422, ['error' => 'La acción administrativa no es válida.']);
} catch (PDOException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec panel administrativo: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo completar la acción administrativa.']);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec panel administrativo: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo completar la acción administrativa.']);
}
