<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(405, ['error' => 'Método no permitido.']);
}

$viewer = current_user();
if (!$viewer) json_response(401, ['error' => 'Inicia sesión para consultar usuarios.']);
$isAdmin = !empty($viewer['is_admin']);
$pdo = db();
if ($isAdmin) require_admin($pdo);

try {
    if (isset($_GET['id'])) {
        $rawUserId = $_GET['id'];
        $userId = is_string($rawUserId) ? filter_var($rawUserId, FILTER_VALIDATE_INT) : false;
        if (!$userId || $userId < 1) json_response(422, ['error' => 'El usuario indicado no es válido.']);

        $query = $pdo->prepare(
            'SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno,
                    u.correo, u.tipo_usuario, u.estado_cuenta, u.ext_aprobada,
                    u.ext_administrador, e.num_control, e.semestre, p.generacion,
                    p.habilidad, p.intereses, p.imagen_perfil,
                    COALESCE(c.nombre, dc.nombre, ec.nombre, \'\') AS afiliacion
             FROM usuarios u
             LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
             LEFT JOIN ext_estudiante_perfil p ON p.id_usuario = u.id_usuario
             LEFT JOIN carreras c ON c.id_carrera = e.id_carrera
             LEFT JOIN docentes d ON d.id_usuario = u.id_usuario
             LEFT JOIN departamentos dc ON dc.id_departamento = d.id_departamento
             LEFT JOIN egresados eg ON eg.id_usuario = u.id_usuario
             LEFT JOIN carreras ec ON ec.id_carrera = eg.id_carrera
             WHERE u.id_usuario = :id'
        );
        $query->execute(['id' => $userId]);
        $profile = $query->fetch();
        $isProfileAdmin = $profile && filter_var($profile['ext_administrador'], FILTER_VALIDATE_BOOLEAN);
        if (!$profile || (!$isAdmin && (
            $isProfileAdmin || $profile['estado_cuenta'] !== 'ACTIVA' ||
            !filter_var($profile['ext_aprobada'], FILTER_VALIDATE_BOOLEAN)
        ))) {
            json_response(404, ['error' => 'No se encontró el perfil solicitado.']);
        }

        $projectVisibility = $isAdmin ? '' : " AND pr.estado = 'PUBLICADO'";
        $projectsQuery = $pdo->prepare(
            "SELECT pr.id_proyecto, pr.titulo, pr.estado,
                    COALESCE(pr.fecha_publicacion, pr.fecha_creacion) AS fecha,
                    CASE WHEN pr.creado_por = :creator THEN 'Creador'
                         ELSE pa.tipo_participacion::text END AS participacion
             FROM proyectos pr
             LEFT JOIN proyectos_autores pa
               ON pa.id_proyecto = pr.id_proyecto AND pa.id_usuario = :author
             WHERE (pr.creado_por = :project_creator OR pa.id_usuario = :project_author)
               {$projectVisibility}
             ORDER BY pr.fecha_creacion DESC, pr.id_proyecto DESC"
        );
        $projectsQuery->execute([
            'creator' => $userId,
            'author' => $userId,
            'project_creator' => $userId,
            'project_author' => $userId,
        ]);
        json_response(200, [
            'profile' => [
                'id_usuario' => (int)$profile['id_usuario'],
                'nombre' => $profile['nombre'],
                'apellido_paterno' => $profile['apellido_paterno'],
                'apellido_materno' => $profile['apellido_materno'],
                'correo' => $isAdmin ? $profile['correo'] : null,
                'tipo_usuario' => $profile['tipo_usuario'],
                'afiliacion' => $profile['afiliacion'],
                'num_control' => $isAdmin ? $profile['num_control'] : null,
                'semestre' => $profile['semestre'],
                'generacion' => $profile['generacion'],
                'habilidad' => $profile['habilidad'],
                'intereses' => $profile['intereses'],
                'imagen_perfil' => $profile['imagen_perfil'],
                'estado_cuenta' => $isAdmin ? $profile['estado_cuenta'] : null,
                'ext_aprobada' => $isAdmin
                    ? filter_var($profile['ext_aprobada'], FILTER_VALIDATE_BOOLEAN)
                    : null,
                'es_administrador' => $isAdmin && $isProfileAdmin,
            ],
            'projects' => $projectsQuery->fetchAll(),
        ]);
    }

    $search = $_GET['q'] ?? '';
    if (!is_string($search)) json_response(422, ['error' => 'El texto de búsqueda no es válido.']);
    $search = trim($search);
    if (mb_strlen($search) > 100) json_response(422, ['error' => 'La búsqueda no puede superar 100 caracteres.']);
    $visibility = $isAdmin
        ? 'TRUE'
        : "(u.estado_cuenta = 'ACTIVA' AND u.ext_aprobada IS TRUE AND u.ext_administrador IS FALSE)";
    $searchClause = '';
    $params = [];
    if ($search !== '') {
        $searchClause = " AND concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno,
                            c.nombre, d.nombre, ec.nombre, u.tipo_usuario) ILIKE :search";
        $params['search'] = '%' . $search . '%';
    }
    $usersQuery = $pdo->prepare(
        "SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno,
                u.tipo_usuario, u.ext_administrador,
                COALESCE(c.nombre, d.nombre, ec.nombre, '') AS afiliacion,
                ep.imagen_perfil
         FROM usuarios u
         LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
         LEFT JOIN carreras c ON c.id_carrera = e.id_carrera
         LEFT JOIN docentes doc ON doc.id_usuario = u.id_usuario
         LEFT JOIN departamentos d ON d.id_departamento = doc.id_departamento
         LEFT JOIN egresados eg ON eg.id_usuario = u.id_usuario
         LEFT JOIN carreras ec ON ec.id_carrera = eg.id_carrera
         LEFT JOIN ext_estudiante_perfil ep ON ep.id_usuario = u.id_usuario
         WHERE {$visibility}{$searchClause}
         ORDER BY u.nombre, u.apellido_paterno, u.id_usuario
         LIMIT 50"
    );
    $usersQuery->execute($params);
    $users = $usersQuery->fetchAll();
    foreach ($users as &$listedUser) {
        $listedUser['id_usuario'] = (int)$listedUser['id_usuario'];
        $listedUser['ext_administrador'] = filter_var($listedUser['ext_administrador'], FILTER_VALIDATE_BOOLEAN);
    }
    unset($listedUser);
    json_response(200, ['users' => $users]);
} catch (Throwable $error) {
    error_log('InvestigaTec búsqueda de usuarios: ' . $error->getMessage());
    json_response(500, ['error' => 'No fue posible consultar los usuarios.']);
}
