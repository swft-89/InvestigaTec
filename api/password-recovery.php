<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/brevo.php';

post_only();
require_csrf();
$input = json_input();
$email = mb_strtolower(trim((string)($input['email'] ?? '')), 'UTF-8');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    json_response(422, ['error' => 'Ingresa un correo electrónico válido.']);
}

$genericResponse = [
    'message' => 'Si el correo está registrado y la cuenta está activa, recibirás instrucciones para restablecer tu contraseña.',
];

try {
    $pdo = db();
    $pdo->beginTransaction();
    $userQuery = $pdo->prepare(
        "SELECT id_usuario, nombre, apellido_paterno, apellido_materno, correo
         FROM usuarios
         WHERE LOWER(correo) = :correo AND estado_cuenta = 'ACTIVA' AND ext_aprobada IS TRUE
         FOR UPDATE"
    );
    $userQuery->execute(['correo' => $email]);
    $user = $userQuery->fetch();
    if (!$user) {
        $pdo->commit();
        json_response(200, $genericResponse);
    }

    $recentQuery = $pdo->prepare(
        'SELECT 1 FROM ext_recuperacion_acceso
         WHERE id_usuario = :usuario
           AND solicitado_en > NOW() - INTERVAL \'2 minutes\'
         LIMIT 1'
    );
    $recentQuery->execute(['usuario' => $user['id_usuario']]);
    if ($recentQuery->fetchColumn()) {
        $pdo->commit();
        json_response(200, $genericResponse);
    }

    $pdo->prepare(
        'UPDATE ext_recuperacion_acceso SET usado = TRUE
         WHERE id_usuario = :usuario AND usado IS FALSE'
    )->execute(['usuario' => $user['id_usuario']]);

    $token = bin2hex(random_bytes(32));
    $insert = $pdo->prepare(
        'INSERT INTO ext_recuperacion_acceso (id_usuario, token_hash, fecha_expiracion, solicitado_en)
         VALUES (:usuario, :hash, NOW() + INTERVAL \'45 minutes\', NOW())
         RETURNING id_recuperacion'
    );
    $insert->execute([
        'usuario' => $user['id_usuario'],
        'hash' => hash('sha256', $token),
    ]);
    $recoveryId = (int)$insert->fetchColumn();
    $pdo->commit();

    try {
        $name = trim(implode(' ', array_filter([
            $user['nombre'],
            $user['apellido_paterno'],
            $user['apellido_materno'],
        ], static fn($part): bool => is_string($part) && $part !== '')));
        $url = application_public_url('?page=recuperar#token=' . rawurlencode($token));
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        brevo_send_email(
            (string)$user['correo'],
            $name,
            'Restablece tu contraseña · InvestigaTec',
            '<p>Recibimos una solicitud para cambiar la contraseña de tu cuenta.</p>' .
                '<p><a href="' . $escapedUrl . '">Restablecer contraseña</a></p>' .
                '<p>El enlace vence en 45 minutos. Si no solicitaste este cambio, ignora este correo.</p>',
            "Recibimos una solicitud para cambiar la contraseña de tu cuenta.\n\n" .
                "Restablece tu contraseña en: {$url}\n\n" .
                'El enlace vence en 45 minutos. Si no solicitaste este cambio, ignora este correo.'
        );
    } catch (Throwable $mailError) {
        $pdo->prepare(
            'UPDATE ext_recuperacion_acceso SET usado = TRUE WHERE id_recuperacion = :id'
        )->execute(['id' => $recoveryId]);
        error_log('InvestigaTec enviar recuperación de contraseña: ' . $mailError->getMessage());
    }

    json_response(200, $genericResponse);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('InvestigaTec solicitud de recuperación: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo procesar la solicitud. Inténtalo más tarde.']);
}
