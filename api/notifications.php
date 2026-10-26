<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para consultar tus notificaciones.']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (($_GET['unread_count'] ?? null) === '1') {
            $countQuery = db()->prepare(
                'SELECT COUNT(*) FROM ext_notificacion
                 WHERE id_usuario = :usuario AND leida IS FALSE'
            );
            $countQuery->execute(['usuario' => $user['id']]);
            json_response(200, ['count' => (int)$countQuery->fetchColumn()]);
        }

        $query = db()->prepare(
            'SELECT n.id_notificacion, n.id_proyecto, n.mensaje, n.fecha_envio,
                    n.leida, p.titulo
             FROM ext_notificacion n
             LEFT JOIN proyectos p ON p.id_proyecto = n.id_proyecto
             WHERE n.id_usuario = :usuario
             ORDER BY n.fecha_envio DESC, n.id_notificacion DESC'
        );
        $query->execute(['usuario' => $user['id']]);
        $notifications = $query->fetchAll();
        foreach ($notifications as &$notification) {
            $notification['leida'] = filter_var($notification['leida'], FILTER_VALIDATE_BOOLEAN);
        }
        unset($notification);
        json_response(200, ['notifications' => $notifications]);
    } catch (Throwable $error) {
        error_log('InvestigaTec consulta notificaciones: ' . $error->getMessage());
        json_response(500, ['error' => 'No se pudieron cargar tus notificaciones.']);
    }
}

post_only();
require_csrf();
$input = json_input();
if (($input['action'] ?? null) !== 'mark_read') {
    json_response(422, ['error' => 'La acción de notificaciones no es válida.']);
}

try {
    $query = db()->prepare(
        'UPDATE ext_notificacion SET leida = TRUE
         WHERE id_usuario = :usuario AND leida IS FALSE'
    );
    $query->execute(['usuario' => $user['id']]);
    json_response(200, ['message' => 'Notificaciones marcadas como leídas.', 'updated' => $query->rowCount()]);
} catch (Throwable $error) {
    error_log('InvestigaTec marcar notificaciones: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudieron marcar las notificaciones como leídas.']);
}
