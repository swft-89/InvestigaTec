<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user = current_user();
if (!$user) json_response(401, ['error' => 'Inicia sesión para consultar tu perfil.']);
if ($user['type'] !== 'ESTUDIANTE') json_response(403, ['error' => 'Solo las cuentas de estudiante tienen perfil editable.']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $query = db()->prepare(
            'SELECT u.nombre, u.apellido_paterno, u.apellido_materno, u.correo,
                    e.num_control, e.id_carrera, e.semestre, p.generacion,
                    p.habilidad, p.intereses, p.imagen_perfil
             FROM usuarios u
             JOIN estudiantes e ON e.id_usuario = u.id_usuario
             LEFT JOIN ext_estudiante_perfil p ON p.id_usuario = u.id_usuario
             WHERE u.id_usuario = :id'
        );
        $query->execute(['id' => $user['id']]);
        $profile = $query->fetch();
        if (!$profile) json_response(404, ['error' => 'No se encontró el perfil de estudiante.']);
        json_response(200, ['profile' => $profile]);
    } catch (Throwable $error) {
        error_log('InvestigaTec consulta perfil: ' . $error->getMessage());
        json_response(500, ['error' => 'No se pudo cargar el perfil. Revisa la conexión y el esquema de PostgreSQL.']);
    }
}

post_only();
require_csrf();
if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) {
    json_response(415, ['error' => 'El perfil debe enviarse como formulario.']);
}

$nombre = trim((string)($_POST['nombre'] ?? ''));
$paterno = trim((string)($_POST['apellido_paterno'] ?? ''));
$materno = trim((string)($_POST['apellido_materno'] ?? ''));
$correo = mb_strtolower(trim((string)($_POST['correo'] ?? '')), 'UTF-8');
$control = trim((string)($_POST['num_control'] ?? ''));
$carrera = filter_var($_POST['id_carrera'] ?? null, FILTER_VALIDATE_INT);
$semestre = ($_POST['semestre'] ?? '') === '' ? null : filter_var($_POST['semestre'], FILTER_VALIDATE_INT);
$generacion = trim((string)($_POST['generacion'] ?? ''));
$habilidad = trim((string)($_POST['habilidad'] ?? ''));
$intereses = trim((string)($_POST['intereses'] ?? ''));

if ($nombre === '' || mb_strlen($nombre) > 100 || $paterno === '' || mb_strlen($paterno) > 100 ||
    mb_strlen($materno) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 255 ||
    !preg_match('/^[a-zA-Z0-9-]{4,20}$/', $control) || !$carrera || $carrera < 1 ||
    ($semestre !== null && (!$semestre || $semestre < 1 || $semestre > 20)) ||
    mb_strlen($generacion) > 20 || mb_strlen($habilidad) > 2000 || mb_strlen($intereses) > 2000) {
    json_response(422, ['error' => 'Revisa los datos del perfil e intenta nuevamente.']);
}

$pdo = null;
$newPhoto = null;
try {
    $pdo = db();
    $careerQuery = $pdo->prepare('SELECT 1 FROM carreras WHERE id_carrera = :id AND activa IS TRUE');
    $careerQuery->execute(['id' => $carrera]);
    if (!$careerQuery->fetchColumn()) json_response(422, ['error' => 'Selecciona una carrera válida y activa.']);

    if (isset($_FILES['portrait']) && $_FILES['portrait']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['portrait'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
            json_response(422, ['error' => 'La foto debe ser JPG o PNG y pesar como máximo 1 MB.']);
        }
        $imageInfo = @getimagesize($file['tmp_name']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!$imageInfo || !isset($extensions[$mime]) || $imageInfo['mime'] !== $mime) {
            json_response(422, ['error' => 'Selecciona una imagen JPG o PNG válida.']);
        }
        $directory = __DIR__ . '/../uploads/perfiles';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio para las fotos de perfil.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            throw new RuntimeException('No se pudo almacenar la foto de perfil.');
        }
        $newPhoto = 'uploads/perfiles/' . $filename;
    }

    $pdo->beginTransaction();
    $existingPhotoQuery = $pdo->prepare('SELECT imagen_perfil FROM ext_estudiante_perfil WHERE id_usuario = :id FOR UPDATE');
    $existingPhotoQuery->execute(['id' => $user['id']]);
    $oldPhoto = $existingPhotoQuery->fetchColumn() ?: null;
    $photo = $newPhoto ?? $oldPhoto;

    $updateUser = $pdo->prepare(
        'UPDATE usuarios SET nombre = :nombre, apellido_paterno = :paterno,
         apellido_materno = :materno, correo = :correo WHERE id_usuario = :id'
    );
    $updateUser->execute([
        'nombre' => $nombre, 'paterno' => $paterno, 'materno' => $materno ?: null,
        'correo' => $correo, 'id' => $user['id'],
    ]);
    $updateStudent = $pdo->prepare(
        'UPDATE estudiantes SET num_control = :control, id_carrera = :carrera, semestre = :semestre
         WHERE id_usuario = :id'
    );
    $updateStudent->execute([
        'control' => $control, 'carrera' => $carrera, 'semestre' => $semestre, 'id' => $user['id'],
    ]);
    $updateProfile = $pdo->prepare(
        'INSERT INTO ext_estudiante_perfil (id_usuario, generacion, habilidad, intereses, imagen_perfil)
         VALUES (:id, :generacion, :habilidad, :intereses, :foto)
         ON CONFLICT (id_usuario) DO UPDATE SET generacion = EXCLUDED.generacion,
         habilidad = EXCLUDED.habilidad, intereses = EXCLUDED.intereses,
         imagen_perfil = EXCLUDED.imagen_perfil'
    );
    $updateProfile->execute([
        'id' => $user['id'], 'generacion' => $generacion ?: null,
        'habilidad' => $habilidad ?: null, 'intereses' => $intereses ?: null, 'foto' => $photo,
    ]);
    $pdo->commit();

    $_SESSION['user']['name'] = trim($nombre . ' ' . $paterno . ' ' . $materno);
    $_SESSION['user']['email'] = $correo;
    $_SESSION['user']['portrait'] = $photo;
    if ($newPhoto && $oldPhoto && str_starts_with($oldPhoto, 'uploads/perfiles/') &&
        basename($oldPhoto) === substr($oldPhoto, strlen('uploads/perfiles/'))) {
        $oldPath = __DIR__ . '/../' . $oldPhoto;
        if (is_file($oldPath)) unlink($oldPath);
    }
    json_response(200, ['message' => 'Perfil actualizado correctamente.']);
} catch (PDOException $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    if ($newPhoto && is_file(__DIR__ . '/../' . $newPhoto)) unlink(__DIR__ . '/../' . $newPhoto);
    if ($error->getCode() === '23505') json_response(409, ['error' => 'El correo o número de control ya está registrado.']);
    error_log('InvestigaTec actualizar perfil: ' . $error->getMessage());
    json_response(500, ['error' => 'No fue posible guardar el perfil.']);
} catch (Throwable $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    if ($newPhoto && is_file(__DIR__ . '/../' . $newPhoto)) unlink(__DIR__ . '/../' . $newPhoto);
    error_log('InvestigaTec actualizar perfil: ' . $error->getMessage());
    json_response(500, ['error' => 'No fue posible guardar el perfil.']);
}
