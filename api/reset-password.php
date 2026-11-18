<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/brevo.php';

post_only();
require_csrf();
$input = json_input();
$token = $input['token'] ?? null;
$password = $input['password'] ?? null;
if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token) ||
    !is_string($password) || strlen($password) < 10 || strlen($password) > 256) {
    json_response(422, ['error' => 'El enlace no es válido o la contraseña no cumple los requisitos.']);
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    $tokenQuery = $pdo->prepare(
        'SELECT r.id_recuperacion, r.id_usuario, u.correo,
                trim(concat_ws(\' \', u.nombre, u.apellido_paterno, u.apellido_materno)) AS nombre
         FROM ext_recuperacion_acceso r
         JOIN usuarios u ON u.id_usuario = r.id_usuario
         WHERE r.token_hash = :hash AND r.usado IS FALSE
           AND r.fecha_expiracion > NOW()
         FOR UPDATE OF r, u'
    );
    $tokenQuery->execute(['hash' => hash('sha256', $token)]);
    $recovery = $tokenQuery->fetch();
    if (!$recovery) {
        $pdo->rollBack();
        json_response(422, ['error' => 'El enlace ya venció o fue utilizado. Solicita uno nuevo.']);
    }

    $update = $pdo->prepare(
        'UPDATE usuarios SET password_hash = :hash WHERE id_usuario = :id'
    );
    $update->execute([
        'hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => $recovery['id_usuario'],
    ]);
    $pdo->prepare(
        'UPDATE ext_recuperacion_acceso SET usado = TRUE
         WHERE id_usuario = :usuario AND usado IS FALSE'
    )->execute(['usuario' => $recovery['id_usuario']]);
    $pdo->commit();

    try {
        brevo_send_email(
            (string)$recovery['correo'],
            (string)$recovery['nombre'],
            'Tu contraseña fue actualizada · InvestigaTec',
            '<p>La contraseña de tu cuenta fue actualizada correctamente.</p>' .
                '<p>Si no realizaste este cambio, contacta al equipo de soporte.</p>',
            "La contraseña de tu cuenta fue actualizada correctamente.\n\n" .
                'Si no realizaste este cambio, contacta al equipo de soporte.'
        );
    } catch (Throwable $mailError) {
        error_log('InvestigaTec confirmación de cambio de contraseña: ' . $mailError->getMessage());
    }

    json_response(200, ['message' => 'Contraseña actualizada. Ya puedes iniciar sesión.']);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec restablecer contraseña: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo cambiar la contraseña. Inténtalo más tarde.']);
}
