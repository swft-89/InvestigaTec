<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(405, ['error' => 'Método no permitido.']);
}
$rawId = $_GET['id'] ?? null;
$projectId = is_string($rawId) || is_int($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
if (!$projectId || $projectId < 1) json_response(422, ['error' => 'El identificador del artículo no es válido.']);

try {
    $pdo = db();
    $pdo->beginTransaction();
    $publish = $pdo->prepare(
        "UPDATE proyectos
         SET estado = 'PUBLICADO',
             fecha_publicacion = COALESCE(fecha_publicacion, NOW())
         WHERE id_proyecto = :id AND estado = 'APROBADO'"
    );
    $publish->execute(['id' => $projectId]);

    $projectQuery = $pdo->prepare(
        "SELECT p.id_proyecto, p.titulo, p.resumen, p.fecha_publicacion::date AS fecha,
                d.nombre AS departamento,
                COALESCE((
                    SELECT string_agg(DISTINCT a.nombre, ', ' ORDER BY a.nombre)
                    FROM proyectos_areas pa
                    JOIN areas_tematicas a ON a.id_area = pa.id_area
                    WHERE pa.id_proyecto = p.id_proyecto
                ), 'Sin área') AS areas,
                COALESCE((
                    SELECT string_agg(
                        DISTINCT trim(concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno)),
                        ', ' ORDER BY trim(concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno))
                    )
                    FROM proyectos_autores pa
                    JOIN usuarios u ON u.id_usuario = pa.id_usuario
                    WHERE pa.id_proyecto = p.id_proyecto AND pa.tipo_participacion = 'AUTOR'
                ), 'Autor no especificado') AS autores,
                COALESCE((
                    SELECT string_agg(DISTINCT pc.nombre, ', ' ORDER BY pc.nombre)
                    FROM proyectos_palabras_clave ppc
                    JOIN palabras_clave pc ON pc.id_palabra_clave = ppc.id_palabra_clave
                    WHERE ppc.id_proyecto = p.id_proyecto
                ), '') AS palabras_clave,
                (
                    SELECT trim(concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno))
                    FROM proyectos_autores pa JOIN usuarios u ON u.id_usuario = pa.id_usuario
                    WHERE pa.id_proyecto = p.id_proyecto AND pa.tipo_participacion = 'ASESOR'
                    ORDER BY pa.orden_autoria NULLS LAST LIMIT 1
                ) AS asesor,
                EXISTS (SELECT 1 FROM archivos a WHERE a.id_proyecto = p.id_proyecto) AS tiene_documento
         FROM proyectos p
         JOIN departamentos d ON d.id_departamento = p.id_departamento
         WHERE p.id_proyecto = :proyecto AND p.estado = 'PUBLICADO'"
    );
    $projectQuery->execute(['proyecto' => $projectId]);
    $project = $projectQuery->fetch();
    if (!$project) {
        $pdo->rollBack();
        json_response(404, ['error' => 'No se encontró un artículo publicado con ese identificador.']);
    }
    $pdo->commit();
    $project['tiene_documento'] = filter_var($project['tiene_documento'], FILTER_VALIDATE_BOOLEAN);
    json_response(200, ['project' => $project]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec consulta artículo público: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo cargar el artículo publicado.']);
}
