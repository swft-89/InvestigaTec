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

        $limit = $_GET['limit'] ?? '5';
        if (!is_string($limit) || !in_array($limit, ['5', '10', '15', 'all'], true)) {
            json_response(422, ['error' => 'La cantidad de notificaciones solicitada no es válida.']);
        }
        $search = $_GET['search'] ?? '';
        if (!is_string($search) || mb_strlen($search) > 200) {
            json_response(422, ['error' => 'El texto de búsqueda no es válido.']);
        }
        $status = $_GET['status'] ?? 'all';
        if (!is_string($status) || !in_array($status, ['all', 'unread', 'read'], true)) {
            json_response(422, ['error' => 'El filtro de notificaciones no es válido.']);
        }

        $sql = 'SELECT n.id_notificacion, n.id_proyecto, n.mensaje, n.fecha_envio,
                       n.leida, p.titulo
                FROM ext_notificacion n
                LEFT JOIN proyectos p ON p.id_proyecto = n.id_proyecto
                WHERE n.id_usuario = :usuario';
        $parameters = ['usuario' => $user['id']];
        if ($search !== '') {
            $sql .= ' AND (n.mensaje ILIKE :message_search OR p.titulo ILIKE :title_search)';
            $parameters['message_search'] = '%' . $search . '%';
            $parameters['title_search'] = '%' . $search . '%';
        }
        if ($status === 'unread') {
            $sql .= ' AND n.leida IS FALSE';
        } elseif ($status === 'read') {
            $sql .= ' AND n.leida IS TRUE';
        }
        $sql .= ' ORDER BY n.fecha_envio DESC, n.id_notificacion DESC';
        if ($limit !== 'all') $sql .= ' LIMIT ' . $limit;

        $query = db()->prepare($sql);
        $query->execute($parameters);
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
$notificationId = filter_var($input['notification_id'] ?? null, FILTER_VALIDATE_INT);
if (!$notificationId || $notificationId < 1) {
    json_response(422, ['error' => 'Indica una notificación válida para marcar como leída.']);
}

try {
    $query = db()->prepare(
        'UPDATE ext_notificacion SET leida = TRUE
         WHERE id_notificacion = :notificacion
           AND id_usuario = :usuario AND leida IS FALSE'
    );
    $query->execute(['notificacion' => $notificationId, 'usuario' => $user['id']]);
    json_response(200, ['message' => 'Notificación marcada como leída.', 'updated' => $query->rowCount()]);
} catch (Throwable $error) {
    error_log('InvestigaTec marcar notificación: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo marcar la notificación como leída.']);
}
