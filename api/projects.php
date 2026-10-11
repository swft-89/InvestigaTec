<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = db();
        $departments = $pdo->query('SELECT id_departamento, nombre FROM departamentos ORDER BY nombre')->fetchAll();
        $areas = $pdo->query('SELECT id_area, nombre FROM areas_tematicas ORDER BY nombre')->fetchAll();
        json_response(200, ['departamentos' => $departments, 'areas_tematicas' => $areas]);
    } catch (Throwable $error) {
        error_log('InvestigaTec opciones de proyecto: ' . $error->getMessage());
        json_response(500, ['error' => 'No se pudieron cargar los departamentos y áreas temáticas.']);
    }
}

post_only();
require_csrf();
$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para registrar un proyecto.']);
if (!in_array($user['type'], ['ESTUDIANTE', 'DOCENTE', 'EGRESADO'], true)) {
    json_response(403, ['error' => 'Tu cuenta no tiene permiso para registrar proyectos.']);
}
if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) {
    json_response(415, ['error' => 'El proyecto debe enviarse como formulario.']);
}

$textField = static fn(string $key): string =>
    isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
$title = $textField('titulo');
$summary = $textField('resumen');
$rawDepartmentId = $_POST['id_departamento'] ?? null;
$departmentId = is_string($rawDepartmentId) || is_int($rawDepartmentId)
    ? filter_var($rawDepartmentId, FILTER_VALIDATE_INT)
    : false;
$rawAreas = $_POST['id_areas'] ?? [];
$keywordsText = $textField('palabras_clave');
$authorsText = $textField('autores');
$advisorEmail = mb_strtolower($textField('asesor_correo'), 'UTF-8');
$publicationDate = $textField('fecha_publicacion');
$state = $textField('estado') ?: 'BORRADOR';

if (!is_array($rawAreas)) json_response(422, ['error' => 'Selecciona al menos un área temática.']);
$areaIds = [];
foreach ($rawAreas as $rawArea) {
    if (!is_string($rawArea) && !is_int($rawArea)) {
        json_response(422, ['error' => 'La selección de áreas temáticas no es válida.']);
    }
    $areaId = filter_var($rawArea, FILTER_VALIDATE_INT);
    if (!$areaId || $areaId < 1) json_response(422, ['error' => 'La selección de áreas temáticas no es válida.']);
    $areaIds[] = $areaId;
}
$areaIds = array_values(array_unique($areaIds));

$keywords = array_values(array_unique(array_filter(
    array_map(static fn(string $value): string => mb_strtolower(trim($value), 'UTF-8'), explode(',', $keywordsText)),
    static fn(string $value): bool => $value !== ''
)));
$authorEmails = array_values(array_unique(array_filter(
    array_map(static fn(string $value): string => mb_strtolower(trim($value), 'UTF-8'), explode(',', $authorsText)),
    static fn(string $value): bool => $value !== ''
)));

$hasInvalidKeyword = false;
foreach ($keywords as $keyword) {
    if (mb_strlen($keyword) > 100) {
        $hasInvalidKeyword = true;
        break;
    }
}
$hasInvalidAuthor = false;
foreach ($authorEmails as $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $hasInvalidAuthor = true;
        break;
    }
}

if ($title === '' || mb_strlen($title) > 300 || $summary === '' || mb_strlen($summary) > 20000 ||
    !$departmentId || $departmentId < 1 || !$areaIds || !$keywords || !$authorEmails ||
    count($keywords) > 30 || count($authorEmails) > 30 ||
    $hasInvalidKeyword || $hasInvalidAuthor ||
    ($advisorEmail !== '' && (!filter_var($advisorEmail, FILTER_VALIDATE_EMAIL) || strlen($advisorEmail) > 255)) ||
    !in_array($state, ['BORRADOR', 'EN_REVISION'], true)) {
    json_response(422, ['error' => 'Revisa el título, resumen, departamento, áreas, palabras clave, autores y estado del proyecto.']);
}

if ($publicationDate !== '') {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $publicationDate);
    if (!$date || $date->format('Y-m-d') !== $publicationDate) {
        json_response(422, ['error' => 'La fecha de publicación no es válida.']);
    }
} else {
    $publicationDate = null;
}

if (!isset($_FILES['pdf'])) json_response(422, ['error' => 'Adjunta el archivo PDF del proyecto.']);
$pdf = $_FILES['pdf'];
$uploadError = $pdf['error'] ?? UPLOAD_ERR_NO_FILE;
if (!is_int($uploadError)) json_response(422, ['error' => 'No se pudo recibir el archivo PDF.']);
if ($uploadError !== UPLOAD_ERR_OK || !is_string($pdf['tmp_name'] ?? null) || !is_uploaded_file($pdf['tmp_name'])) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'El PDF supera el límite permitido por el servidor.',
        UPLOAD_ERR_FORM_SIZE => 'El PDF supera el límite permitido por el formulario.',
        UPLOAD_ERR_PARTIAL => 'El PDF se recibió incompleto. Intenta subirlo nuevamente.',
        UPLOAD_ERR_NO_FILE => 'Adjunta el archivo PDF del proyecto.',
    ];
    json_response(422, ['error' => $uploadErrors[$uploadError] ?? 'No se pudo recibir el archivo PDF.']);
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
if (!is_string($uploadedName)) json_response(422, ['error' => 'El nombre del archivo PDF no es válido.']);
$originalName = basename(str_replace('\\', '/', $uploadedName));
if ($originalName === '' || mb_strlen($originalName) > 255) {
    json_response(422, ['error' => 'El nombre del archivo PDF no es válido.']);
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

    $authorIds = [];
    $authorQuery = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = :correo AND estado_cuenta = \'ACTIVA\'');
    foreach ($authorEmails as $email) {
        $authorQuery->execute(['correo' => $email]);
        $authorId = $authorQuery->fetchColumn();
        if (!$authorId) json_response(422, ['error' => 'El usuario ' . $email . ' no existe o su cuenta no está activa.']);
        $authorIds[] = (int)$authorId;
    }
    if ($advisorEmail !== '') {
        $advisorQuery = $pdo->prepare(
            "SELECT id_usuario FROM usuarios
             WHERE correo = :correo AND tipo_usuario = 'DOCENTE' AND estado_cuenta = 'ACTIVA'"
        );
        $advisorQuery->execute(['correo' => $advisorEmail]);
        $advisorId = $advisorQuery->fetchColumn();
        if (!$advisorId) json_response(422, ['error' => 'El asesor debe ser un docente registrado con cuenta activa.']);
        if (in_array((int)$advisorId, $authorIds, true)) {
            json_response(422, ['error' => 'El asesor no puede repetirse como autor del proyecto.']);
        }
    }

    $pdo->beginTransaction();
    $projectQuery = $pdo->prepare(
        'INSERT INTO proyectos
            (titulo, resumen, id_departamento, fecha_publicacion, estado, creado_por)
         VALUES (:titulo, :resumen, :departamento, :fecha, :estado, :creado_por)
         RETURNING id_proyecto'
    );
    $projectQuery->execute([
        'titulo' => $title,
        'resumen' => $summary,
        'departamento' => $departmentId,
        'fecha' => $publicationDate,
        'estado' => $state,
        'creado_por' => $user['id'],
    ]);
    $projectId = (int)$projectQuery->fetchColumn();

    $projectAreaQuery = $pdo->prepare(
        'INSERT INTO proyectos_areas (id_proyecto, id_area) VALUES (:proyecto, :area)'
    );
    foreach ($areaIds as $areaId) {
        $projectAreaQuery->execute(['proyecto' => $projectId, 'area' => $areaId]);
    }

    $keywordInsert = $pdo->prepare(
        'INSERT INTO palabras_clave (nombre) VALUES (:nombre) ON CONFLICT (nombre) DO NOTHING'
    );
    $keywordSelect = $pdo->prepare('SELECT id_palabra_clave FROM palabras_clave WHERE nombre = :nombre');
    $projectKeyword = $pdo->prepare(
        'INSERT INTO proyectos_palabras_clave (id_proyecto, id_palabra_clave) VALUES (:proyecto, :palabra)'
    );
    foreach ($keywords as $keyword) {
        $keywordInsert->execute(['nombre' => $keyword]);
        $keywordSelect->execute(['nombre' => $keyword]);
        $keywordId = $keywordSelect->fetchColumn();
        if (!$keywordId) throw new RuntimeException('No se pudo registrar una palabra clave.');
        $projectKeyword->execute(['proyecto' => $projectId, 'palabra' => $keywordId]);
    }

    $projectAuthor = $pdo->prepare(
        "INSERT INTO proyectos_autores
            (id_proyecto, id_usuario, tipo_participacion, orden_autoria)
         VALUES (:proyecto, :usuario, 'AUTOR', :orden)"
    );
    foreach ($authorIds as $index => $authorId) {
        $projectAuthor->execute(['proyecto' => $projectId, 'usuario' => $authorId, 'orden' => $index + 1]);
    }
    if (!empty($advisorId)) {
        $projectAdvisor = $pdo->prepare(
            "INSERT INTO proyectos_autores (id_proyecto, id_usuario, tipo_participacion)
             VALUES (:proyecto, :usuario, 'ASESOR')"
        );
        $projectAdvisor->execute(['proyecto' => $projectId, 'usuario' => $advisorId]);
    }

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

    $fileQuery = $pdo->prepare(
        'INSERT INTO archivos
            (id_proyecto, nombre_original, nombre_almacenado, tipo_mime, tamanio, ruta_archivo, subido_por)
         VALUES (:proyecto, :original, :almacenado, :mime, :tamanio, :ruta, :subido_por)'
    );
    $fileQuery->execute([
        'proyecto' => $projectId,
        'original' => $originalName,
        'almacenado' => $storedName,
        'mime' => 'application/pdf',
        'tamanio' => $fileSize,
        'ruta' => $storedPath,
        'subido_por' => $user['id'],
    ]);

    $versionQuery = $pdo->prepare(
        'INSERT INTO ext_version_proyecto (id_proyecto, numero_version, ruta_pdf, tamano_bytes)
         VALUES (:proyecto, 1, :ruta, :tamanio)'
    );
    $versionQuery->execute([
        'proyecto' => $projectId,
        'ruta' => $storedPath,
        'tamanio' => $fileSize,
    ]);

    $historyQuery = $pdo->prepare(
        'INSERT INTO historial_estados
            (id_proyecto, estado_anterior, estado_nuevo, cambiado_por)
         VALUES (:proyecto, NULL, :estado, :usuario)'
    );
    $historyQuery->execute([
        'proyecto' => $projectId,
        'estado' => $state,
        'usuario' => $user['id'],
    ]);

    $pdo->commit();
    json_response(201, ['message' => 'Proyecto registrado correctamente.', 'id_proyecto' => $projectId]);
} catch (PDOException $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    if ($storedPath && is_file(__DIR__ . '/../' . $storedPath)) unlink(__DIR__ . '/../' . $storedPath);
    if ($error->getCode() === '23505') json_response(409, ['error' => 'Uno de los autores ya fue agregado al proyecto.']);
    error_log('InvestigaTec crear proyecto: ' . $error->getMessage());
    json_response(500, ['error' => 'No fue posible registrar el proyecto.']);
} catch (Throwable $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    if ($storedPath && is_file(__DIR__ . '/../' . $storedPath)) unlink(__DIR__ . '/../' . $storedPath);
    error_log('InvestigaTec crear proyecto: ' . $error->getMessage());
    json_response(500, ['error' => 'No fue posible registrar el proyecto.']);
}
