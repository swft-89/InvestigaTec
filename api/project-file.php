<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(405, ['error' => 'Método no permitido.']);
}
$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para ver este documento.']);
$rawId = $_GET['id'] ?? null;
$projectId = is_string($rawId) || is_int($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
if (!$projectId || $projectId < 1) json_response(422, ['error' => 'El identificador del artículo no es válido.']);

try {
    $canViewAllProjects = $user['type'] === 'DOCENTE' || !empty($user['is_admin']);
    $accessCondition = $canViewAllProjects
        ? 'TRUE'
        : "p.creado_por = :creador OR EXISTS (
                SELECT 1 FROM proyectos_autores pa
                WHERE pa.id_proyecto = p.id_proyecto
                  AND pa.id_usuario = :autor AND pa.tipo_participacion = 'AUTOR'
           )";
    $query = db()->prepare(
        "SELECT a.ruta_archivo, a.nombre_original
         FROM archivos a JOIN proyectos p ON p.id_proyecto = a.id_proyecto
         WHERE p.id_proyecto = :proyecto
           AND (" . $accessCondition . ")
         ORDER BY a.fecha_subida DESC, a.id_archivo DESC LIMIT 1"
    );
    $queryParameters = ['proyecto' => $projectId];
    if (!$canViewAllProjects) {
        $queryParameters['creador'] = $user['id'];
        $queryParameters['autor'] = $user['id'];
    }
    $query->execute($queryParameters);
    $file = $query->fetch();
    if (!$file) json_response(404, ['error' => 'No se encontró el documento o no tienes acceso.']);

    $relativePath = $file['ruta_archivo'];
    $expectedPrefix = 'uploads/proyectos/' . $projectId . '/';
    if (!is_string($relativePath) || !str_starts_with($relativePath, $expectedPrefix) ||
        basename($relativePath) !== substr($relativePath, strlen($expectedPrefix))) {
        throw new RuntimeException('La ruta almacenada del documento no es válida.');
    }
    $uploadRoot = realpath(__DIR__ . '/../uploads/proyectos/' . $projectId);
    $physicalPath = realpath(__DIR__ . '/../' . $relativePath);
    if ($uploadRoot === false || $physicalPath === false ||
        !str_starts_with($physicalPath, $uploadRoot . DIRECTORY_SEPARATOR) || !is_file($physicalPath)) {
        json_response(404, ['error' => 'No se encontró el archivo PDF.']);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="articulo-' . (int)$projectId . '.pdf"');
    header('Content-Length: ' . (string)filesize($physicalPath));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($physicalPath);
} catch (Throwable $error) {
    error_log('InvestigaTec vista previa de artículo: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo abrir el documento PDF.']);
}
