<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(405, ['error' => 'Método no permitido.']);
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->exec(
        "UPDATE proyectos
         SET estado = 'PUBLICADO',
             fecha_publicacion = COALESCE(fecha_publicacion, NOW())
         WHERE estado = 'APROBADO'"
    );
    $articles = $pdo->query(
        "SELECT p.id_proyecto, p.titulo, p.resumen,
                COALESCE(p.fecha_publicacion, p.fecha_creacion)::date AS fecha,
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
                ), '') AS palabras_clave
         FROM proyectos p
         WHERE p.estado = 'PUBLICADO'
         ORDER BY COALESCE(p.fecha_publicacion, p.fecha_creacion) DESC, p.id_proyecto DESC"
    )->fetchAll();

    $stats = null;
    $user = current_user();
    if ($user) {
        $statsQuery = $pdo->prepare(
            "SELECT
                COUNT(DISTINCT p.id_proyecto) AS registrados,
                COUNT(DISTINCT p.id_proyecto) FILTER (WHERE p.estado = 'PUBLICADO') AS publicados,
                COUNT(DISTINCT pa.id_area) AS areas
             FROM proyectos p
             LEFT JOIN proyectos_areas pa ON pa.id_proyecto = p.id_proyecto
             WHERE p.creado_por = :creador
                OR EXISTS (
                    SELECT 1 FROM proyectos_autores autor
                    WHERE autor.id_proyecto = p.id_proyecto
                      AND autor.id_usuario = :autor
                      AND autor.tipo_participacion = 'AUTOR'
                )"
        );
        $statsQuery->execute(['creador' => $user['id'], 'autor' => $user['id']]);
        $stats = $statsQuery->fetch();
        $stats = array_map('intval', $stats);
    }
    $pdo->commit();
    json_response(200, ['articles' => $articles, 'stats' => $stats]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec catálogo público: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudieron cargar los artículos publicados.']);
}
