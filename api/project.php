<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para consultar este artículo.']);

$rawId = $_GET['id'] ?? null;
$projectId = is_string($rawId) || is_int($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
if (!$projectId || $projectId < 1) json_response(422, ['error' => 'El identificador del artículo no es válido.']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = db();
        $canViewAllProjects = $user['type'] === 'DOCENTE' || !empty($user['is_admin']);
        $accessCondition = $canViewAllProjects
            ? 'TRUE'
            : "p.creado_por = :creador OR EXISTS (
                    SELECT 1 FROM proyectos_autores pa
                    WHERE pa.id_proyecto = p.id_proyecto
                      AND pa.id_usuario = :autor_acceso AND pa.tipo_participacion = 'AUTOR'
               )";
        $projectQuery = $pdo->prepare(
            "SELECT p.id_proyecto, p.titulo, p.resumen, p.estado, p.id_departamento, p.creado_por,
                    d.nombre AS departamento, p.fecha_creacion::date AS fecha_creacion,
                    p.fecha_publicacion::date AS fecha_publicacion,
                    (
                        SELECT a.nombre_original FROM archivos a
                        WHERE a.id_proyecto = p.id_proyecto
                        ORDER BY a.fecha_subida DESC, a.id_archivo DESC LIMIT 1
                    ) AS archivo,
                    (
                        SELECT MAX(v.numero_version) FROM ext_version_proyecto v
                        WHERE v.id_proyecto = p.id_proyecto
                    ) AS version
             FROM proyectos p
             JOIN departamentos d ON d.id_departamento = p.id_departamento
             WHERE p.id_proyecto = :proyecto
               AND (" . $accessCondition . ')'
        );
        $projectParameters = ['proyecto' => $projectId];
        if (!$canViewAllProjects) {
            $projectParameters['creador'] = $user['id'];
            $projectParameters['autor_acceso'] = $user['id'];
        }
        $projectQuery->execute($projectParameters);
        $project = $projectQuery->fetch();
        if (!$project) json_response(404, ['error' => 'No se encontró el artículo o no tienes acceso.']);

        $areasQuery = $pdo->prepare(
            'SELECT a.id_area, a.nombre FROM proyectos_areas pa
             JOIN areas_tematicas a ON a.id_area = pa.id_area
             WHERE pa.id_proyecto = :id ORDER BY a.nombre'
        );
        $areasQuery->execute(['id' => $projectId]);
        $areaRows = $areasQuery->fetchAll();
        $keywordsQuery = $pdo->prepare(
            'SELECT pc.nombre FROM proyectos_palabras_clave ppc
             JOIN palabras_clave pc ON pc.id_palabra_clave = ppc.id_palabra_clave
             WHERE ppc.id_proyecto = :id ORDER BY pc.nombre'
        );
        $keywordsQuery->execute(['id' => $projectId]);
        $authorsQuery = $pdo->prepare(
            "SELECT trim(concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno)) AS nombre
             FROM proyectos_autores pa JOIN usuarios u ON u.id_usuario = pa.id_usuario
             WHERE pa.id_proyecto = :id AND pa.tipo_participacion = 'AUTOR'
             ORDER BY pa.orden_autoria NULLS LAST, nombre"
        );
        $authorsQuery->execute(['id' => $projectId]);
        $advisorQuery = $pdo->prepare(
            "SELECT trim(concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno)) AS nombre
             FROM proyectos_autores pa JOIN usuarios u ON u.id_usuario = pa.id_usuario
             WHERE pa.id_proyecto = :id AND pa.tipo_participacion = 'ASESOR'
             ORDER BY pa.orden_autoria NULLS LAST LIMIT 1"
        );
        $advisorQuery->execute(['id' => $projectId]);
        $project['areas'] = array_column($areaRows, 'nombre');
        $project['area_ids'] = array_map('intval', array_column($areaRows, 'id_area'));
        $project['palabras_clave'] = $keywordsQuery->fetchAll(PDO::FETCH_COLUMN);
        $project['autores'] = $authorsQuery->fetchAll(PDO::FETCH_COLUMN);
        $project['asesor'] = $advisorQuery->fetchColumn() ?: null;
        $project['has_open_report'] = false;
        if ($user['type'] === 'DOCENTE' || !empty($user['is_admin'])) {
            $reportAccessQuery = $pdo->prepare(
                "SELECT COUNT(*) FROM ext_reporte_proyecto
                 WHERE id_proyecto = :proyecto AND id_reportado_por = :usuario AND estado <> 'RESUELTO'"
            );
            $reportAccessQuery->execute(['proyecto' => $projectId, 'usuario' => $user['id']]);
            $project['has_open_report'] = (int)$reportAccessQuery->fetchColumn() > 0;
        }
        $project['can_report'] = (!empty($user['is_admin']) || $project['estado'] !== 'PUBLICADO')
            && !$project['has_open_report'];
        $project['can_edit'] = (int)$project['creado_por'] === (int)$user['id'];
        if (!$project['can_edit']) {
            $authorAccessQuery = $pdo->prepare(
                "SELECT CASE WHEN EXISTS (
                    SELECT 1 FROM proyectos_autores
                    WHERE id_proyecto = :proyecto AND id_usuario = :usuario
                      AND tipo_participacion = 'AUTOR'
                ) THEN 1 ELSE 0 END"
            );
            $authorAccessQuery->execute(['proyecto' => $projectId, 'usuario' => $user['id']]);
            $project['can_edit'] = (int)$authorAccessQuery->fetchColumn() === 1;
        }
        unset($project['creado_por']);

        $departments = $pdo->query('SELECT id_departamento, nombre FROM departamentos ORDER BY nombre')->fetchAll();
        $areas = $pdo->query('SELECT id_area, nombre FROM areas_tematicas ORDER BY nombre')->fetchAll();
        json_response(200, [
            'project' => $project,
            'departamentos' => $departments,
            'areas_tematicas' => $areas,
        ]);
    } catch (Throwable $error) {
        error_log('InvestigaTec consulta artículo: ' . $error->getMessage());
        json_response(500, ['error' => 'No se pudo cargar el artículo.']);
    }
}

post_only();
require_csrf();
if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) {
    json_response(415, ['error' => 'Los cambios del artículo deben enviarse como formulario.']);
}

$textField = static fn(string $key): string =>
    isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
$title = $textField('titulo');
$summary = $textField('resumen');
$rawDepartmentId = $_POST['id_departamento'] ?? null;
$departmentId = is_string($rawDepartmentId) || is_int($rawDepartmentId)
    ? filter_var($rawDepartmentId, FILTER_VALIDATE_INT) : false;
$rawAreas = $_POST['id_areas'] ?? [];
$keywordsText = $textField('palabras_clave');
$keywords = array_values(array_unique(array_filter(
    array_map(static fn(string $value): string => mb_strtolower(trim($value), 'UTF-8'), explode(',', $keywordsText)),
    static fn(string $value): bool => $value !== ''
)));
$publicationDate = $textField('fecha_publicacion');

if (!is_array($rawAreas)) json_response(422, ['error' => 'Selecciona al menos un área temática.']);
$areaIds = [];
foreach ($rawAreas as $rawArea) {
    if (!is_string($rawArea) && !is_int($rawArea)) json_response(422, ['error' => 'La selección de áreas no es válida.']);
    $areaId = filter_var($rawArea, FILTER_VALIDATE_INT);
    if (!$areaId || $areaId < 1) json_response(422, ['error' => 'La selección de áreas no es válida.']);
    $areaIds[] = $areaId;
}
$areaIds = array_values(array_unique($areaIds));
if (count($areaIds) > 3) json_response(422, ['error' => 'Selecciona como máximo 3 áreas temáticas.']);
$invalidKeyword = false;
foreach ($keywords as $keyword) {
    if (mb_strlen($keyword) > 100) {
        $invalidKeyword = true;
        break;
    }
}
if ($title === '' || mb_strlen($title) > 300 || $summary === '' || mb_strlen($summary) > 20000 ||
    !$departmentId || $departmentId < 1 || !$areaIds ||
    mb_strlen($keywordsText) > 3000 || !$keywords || count($keywords) > 30 || $invalidKeyword) {
    json_response(422, ['error' => 'Revisa el título, resumen, departamento, áreas y palabras clave.']);
}
if ($publicationDate !== '') {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $publicationDate);
    if (!$date || $date->format('Y-m-d') !== $publicationDate) {
        json_response(422, ['error' => 'La fecha de publicación no es válida.']);
    }
} else {
    $publicationDate = null;
}

$pdf = $_FILES['pdf'] ?? null;
$hasPdf = is_array($pdf) && ($pdf['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
$originalName = null;
$fileSize = null;
if ($hasPdf) {
    $uploadError = $pdf['error'] ?? UPLOAD_ERR_NO_FILE;
    if (!is_int($uploadError) || $uploadError !== UPLOAD_ERR_OK ||
        !is_string($pdf['tmp_name'] ?? null) || !is_uploaded_file($pdf['tmp_name'])) {
        json_response(422, ['error' => 'No se pudo recibir el PDF nuevo.']);
    }
    $fileSize = $pdf['size'] ?? null;
    if (!is_int($fileSize) || $fileSize < 1 || $fileSize > 20 * 1024 * 1024) {
        json_response(422, ['error' => 'El PDF debe pesar más de 0 bytes y no superar 20 MB.']);
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($pdf['tmp_name']);
    $signature = file_get_contents($pdf['tmp_name'], false, null, 0, 5);
    if ($mime !== 'application/pdf' || $signature !== '%PDF-') {
        json_response(422, ['error' => 'El archivo debe ser un PDF válido.']);
    }
    $uploadedName = $pdf['name'] ?? null;
    if (!is_string($uploadedName)) json_response(422, ['error' => 'El nombre del PDF no es válido.']);
    $originalName = basename(str_replace('\\', '/', $uploadedName));
    if ($originalName === '' || mb_strlen($originalName) > 255) {
        json_response(422, ['error' => 'El nombre del PDF no es válido.']);
    }
}

$pdo = null;
$storedPath = null;
try {
    $pdo = db();
    $departmentQuery = $pdo->prepare('SELECT 1 FROM departamentos WHERE id_departamento = :id');
    $departmentQuery->execute(['id' => $departmentId]);
    if (!$departmentQuery->fetchColumn()) json_response(422, ['error' => 'Selecciona un departamento válido.']);

    $areaQuery = $pdo->prepare('SELECT id_area FROM areas_tematicas WHERE id_area = ANY(CAST(:ids AS INTEGER[]))');
    $areaQuery->execute(['ids' => '{' . implode(',', $areaIds) . '}']);
    $validAreaIds = array_map('intval', $areaQuery->fetchAll(PDO::FETCH_COLUMN));
    sort($validAreaIds);
    $expectedAreaIds = $areaIds;
    sort($expectedAreaIds);
    if ($validAreaIds !== $expectedAreaIds) json_response(422, ['error' => 'Una o más áreas temáticas ya no están disponibles.']);

    $pdo->beginTransaction();
    $lockQuery = $pdo->prepare(
        'SELECT estado, creado_por FROM proyectos WHERE id_proyecto = :id FOR UPDATE'
    );
    $lockQuery->execute(['id' => $projectId]);
    $current = $lockQuery->fetch();
    if (!$current) {
        $pdo->rollBack();
        json_response(404, ['error' => 'No se encontró el artículo.']);
    }
    if ((int)$current['creado_por'] !== (int)$user['id']) {
        $authorAccessQuery = $pdo->prepare(
            "SELECT 1 FROM proyectos_autores
             WHERE id_proyecto = :proyecto AND id_usuario = :usuario
               AND tipo_participacion = 'AUTOR'"
        );
        $authorAccessQuery->execute(['proyecto' => $projectId, 'usuario' => $user['id']]);
        if (!$authorAccessQuery->fetchColumn()) {
            $pdo->rollBack();
            json_response(403, ['error' => 'Solo el creador o los autores pueden editar este artículo.']);
        }
    }
    if (!in_array($current['estado'], ['BORRADOR', 'REQUIERE_CAMBIOS'], true)) {
        $pdo->rollBack();
        json_response(409, ['error' => 'El artículo ya no se encuentra en un estado editable.']);
    }

    $update = $pdo->prepare(
        'UPDATE proyectos SET titulo = :titulo, resumen = :resumen,
         id_departamento = :departamento, fecha_publicacion = :fecha
         WHERE id_proyecto = :id'
    );
    $update->execute([
        'titulo' => $title, 'resumen' => $summary, 'departamento' => $departmentId,
        'fecha' => $publicationDate, 'id' => $projectId,
    ]);
    $pdo->prepare('DELETE FROM proyectos_areas WHERE id_proyecto = :id')->execute(['id' => $projectId]);
    $insertArea = $pdo->prepare('INSERT INTO proyectos_areas (id_proyecto, id_area) VALUES (:proyecto, :area)');
    foreach ($areaIds as $areaId) $insertArea->execute(['proyecto' => $projectId, 'area' => $areaId]);

    $pdo->prepare('DELETE FROM proyectos_palabras_clave WHERE id_proyecto = :id')->execute(['id' => $projectId]);
    $insertKeyword = $pdo->prepare(
        'INSERT INTO palabras_clave (nombre) VALUES (:nombre) ON CONFLICT (nombre) DO NOTHING'
    );
    $selectKeyword = $pdo->prepare('SELECT id_palabra_clave FROM palabras_clave WHERE nombre = :nombre');
    $linkKeyword = $pdo->prepare(
        'INSERT INTO proyectos_palabras_clave (id_proyecto, id_palabra_clave) VALUES (:proyecto, :palabra)'
    );
    foreach ($keywords as $keyword) {
        $insertKeyword->execute(['nombre' => $keyword]);
        $selectKeyword->execute(['nombre' => $keyword]);
        $keywordId = $selectKeyword->fetchColumn();
        if (!$keywordId) throw new RuntimeException('No se pudo guardar una palabra clave.');
        $linkKeyword->execute(['proyecto' => $projectId, 'palabra' => $keywordId]);
    }

    if ($hasPdf) {
        $directory = __DIR__ . '/../uploads/proyectos/' . $projectId;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio para almacenar el PDF.');
        }
        $storedName = bin2hex(random_bytes(16)) . '.pdf';
        $physicalPath = $directory . '/' . $storedName;
        if (!move_uploaded_file($pdf['tmp_name'], $physicalPath)) {
            throw new RuntimeException('No se pudo almacenar el PDF del proyecto.');
        }
        $storedPath = 'uploads/proyectos/' . $projectId . '/' . $storedName;
        $pdo->prepare(
            'INSERT INTO archivos
                (id_proyecto, nombre_original, nombre_almacenado, tipo_mime, tamanio, ruta_archivo, subido_por)
             VALUES (:proyecto, :original, :almacenado, :mime, :tamanio, :ruta, :subido_por)'
        )->execute([
            'proyecto' => $projectId, 'original' => $originalName, 'almacenado' => $storedName,
            'mime' => 'application/pdf', 'tamanio' => $fileSize, 'ruta' => $storedPath,
            'subido_por' => $user['id'],
        ]);
        $versionQuery = $pdo->prepare(
            'INSERT INTO ext_version_proyecto (id_proyecto, numero_version, ruta_pdf, tamano_bytes)
             SELECT :proyecto, COALESCE(MAX(numero_version), 0) + 1, :ruta, :tamanio
             FROM ext_version_proyecto WHERE id_proyecto = :proyecto_version'
        );
        $versionQuery->execute([
            'proyecto' => $projectId, 'ruta' => $storedPath, 'tamanio' => $fileSize,
            'proyecto_version' => $projectId,
        ]);
    }

    $pdo->commit();
    json_response(200, ['message' => 'Artículo actualizado correctamente.']);
} catch (PDOException $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    if ($storedPath && is_file(__DIR__ . '/../' . $storedPath)) unlink(__DIR__ . '/../' . $storedPath);
    error_log('InvestigaTec actualizar artículo: ' . $error->getMessage());
    json_response(500, ['error' => 'No fue posible guardar los cambios del artículo.']);
} catch (Throwable $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    if ($storedPath && is_file(__DIR__ . '/../' . $storedPath)) unlink(__DIR__ . '/../' . $storedPath);
    error_log('InvestigaTec actualizar artículo: ' . $error->getMessage());
    json_response(500, ['error' => 'No fue posible guardar los cambios del artículo.']);
}
