<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
post_only(); require_csrf();
$d = json_input();
$nombre = trim((string)($d['nombre'] ?? ''));
$paterno = trim((string)($d['apellido_paterno'] ?? ''));
$materno = trim((string)($d['apellido_materno'] ?? ''));
$correo = mb_strtolower(trim((string)($d['correo'] ?? '')), 'UTF-8');
$clave = (string)($d['password'] ?? '');
$control = trim((string)($d['num_control'] ?? ''));
$carrera = filter_var($d['id_carrera'] ?? null, FILTER_VALIDATE_INT);
$semestre = ($d['semestre'] ?? '') === '' ? null : filter_var($d['semestre'], FILTER_VALIDATE_INT);
if ($nombre === '' || mb_strlen($nombre) > 100 || $paterno === '' || mb_strlen($paterno) > 100 || mb_strlen($materno) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 255 || strlen($clave) < 10 || strlen($clave) > 256 ||
    !preg_match('/^[a-zA-Z0-9-]{4,20}$/', $control) || !$carrera || $carrera < 1 ||
    ($semestre !== null && (!$semestre || $semestre < 1 || $semestre > 20))) {
    json_response(422,['error'=>'Revisa los datos: nombre, apellidos, correo, matrícula, carrera, semestre y contraseña de al menos 10 caracteres.']);
}
try {
    $pdo = db();
    $q = $pdo->prepare('SELECT 1 FROM carreras WHERE id_carrera = :id AND activa IS TRUE');
    $q->execute(['id'=>$carrera]);
    if (!$q->fetchColumn()) json_response(422,['error'=>'Selecciona una carrera válida y activa.']);
    $pdo->beginTransaction();
    $q = $pdo->prepare("INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, correo, password_hash, tipo_usuario) VALUES (:nombre, :paterno, :materno, :correo, :hash, 'ESTUDIANTE') RETURNING id_usuario");
    $q->execute(['nombre'=>$nombre, 'paterno'=>$paterno, 'materno'=>$materno ?: null, 'correo'=>$correo, 'hash'=>password_hash($clave, PASSWORD_DEFAULT)]);
    $id = (int)$q->fetchColumn();
    $q = $pdo->prepare('INSERT INTO estudiantes (id_usuario, num_control, id_carrera, semestre) VALUES (:id, :control, :carrera, :semestre)');
    $q->execute(['id'=>$id,'control'=>$control,'carrera'=>$carrera,'semestre'=>$semestre]);
    $pdo->commit();
    // La cuenta queda pendiente; solo un administrador puede aprobarla.
    json_response(201,['message'=>'Cuenta creada. El administrador debe aprobarla antes de que puedas iniciar sesión.']);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23505') json_response(409,['error'=>'El correo o número de control ya está registrado.']);
    error_log('InvestigaTec registro: '.$e->getMessage());
    json_response(500,['error'=>'No fue posible registrar la cuenta.']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec registro: '.$e->getMessage());
    json_response(500,['error'=>'No se pudo conectar con la base de datos.']);
}
