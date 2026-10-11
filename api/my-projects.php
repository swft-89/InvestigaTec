<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(405, ['error' => 'Método no permitido.']);
}

$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para consultar tus investigaciones.']);

try {
    $query = db()->prepare(
        "SELECT p.id_proyecto, p.titulo, p.estado,
                COALESCE(p.fecha_publicacion, p.fecha_creacion)::date AS fecha,
                COALESCE((
                    SELECT string_agg(DISTINCT area.nombre, ', ' ORDER BY area.nombre)
                    FROM proyectos_areas proyecto_area
                    JOIN areas_tematicas area ON area.id_area = proyecto_area.id_area
                    WHERE proyecto_area.id_proyecto = p.id_proyecto
                ), '—') AS areas,
                COALESCE((
                    SELECT MAX(version.numero_version)
                    FROM ext_version_proyecto version
                    WHERE version.id_proyecto = p.id_proyecto
                ), 0) AS version,
                (
                    SELECT archivo.nombre_original
                    FROM archivos archivo
                    WHERE archivo.id_proyecto = p.id_proyecto
                    ORDER BY archivo.fecha_subida DESC, archivo.id_archivo DESC
                    LIMIT 1
                ) AS archivo
         FROM proyectos p
         WHERE p.creado_por = :creador
            OR EXISTS (
                SELECT 1
                FROM proyectos_autores autor
                WHERE autor.id_proyecto = p.id_proyecto
                  AND autor.id_usuario = :autor
                  AND autor.tipo_participacion = 'AUTOR'
            )
         ORDER BY p.fecha_creacion DESC, p.id_proyecto DESC"
    );
    $query->execute(['creador' => $user['id'], 'autor' => $user['id']]);
    json_response(200, ['projects' => $query->fetchAll()]);
} catch (Throwable $error) {
    error_log('InvestigaTec mis artículos: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudieron cargar tus investigaciones.']);
}
