<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
post_only(); require_csrf();
$d = json_input();
$correo = mb_strtolower(trim((string)($d['correo'] ?? '')), 'UTF-8');
$password = (string)($d['password'] ?? '');
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $password === '') json_response(422,['error'=>'Ingresa un correo y una contraseña válidos.']);
try {
    $pdo = db();
    $q = $pdo->prepare(
        'SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno, u.correo,
                u.password_hash, u.tipo_usuario, u.estado_cuenta, p.imagen_perfil
         FROM usuarios u
         LEFT JOIN ext_estudiante_perfil p ON p.id_usuario = u.id_usuario
         WHERE u.correo = :correo'
    );
    $q->execute(['correo'=>$correo]);
    $u = $q->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) json_response(401,['error'=>'Correo o contraseña incorrectos.']);
    if ($u['estado_cuenta'] !== 'ACTIVA') json_response(403,['error'=>'La cuenta no está activa.']);
    $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id')->execute(['id'=>$u['id_usuario']]);
    login_user($u);
    json_response(200,['message'=>'Sesión iniciada correctamente.', 'user'=>current_user(), 'csrf'=>csrf_token()]);
} catch (Throwable $e) {
    error_log('InvestigaTec login: '.$e->getMessage());
    json_response(500,['error'=>'No fue posible iniciar sesión. Revisa la conexión de PostgreSQL.']);
}
