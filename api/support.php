<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

post_only();
require_csrf();
$input = json_input();
$user = current_user();

$type = $input['tipo'] ?? null;
$subject = $input['asunto'] ?? null;
$message = $input['mensaje'] ?? null;
$name = $user ? ($user['name'] ?? null) : ($input['nombre'] ?? null);
$email = $user ? ($user['email'] ?? null) : ($input['correo'] ?? null);

if (!is_string($type) || !in_array($type, ['AYUDA', 'SUGERENCIA', 'PROBLEMA'], true) ||
    !is_string($subject) || !is_string($message) || !is_string($name) || !is_string($email)) {
    json_response(422, ['error' => 'Revisa el tipo de solicitud, tus datos de contacto, el asunto y el mensaje.']);
}

$subject = trim($subject);
$message = trim($message);
$name = trim($name);
$email = mb_strtolower(trim($email), 'UTF-8');
if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150 ||
    mb_strlen($message) < 10 || mb_strlen($message) > 5000 ||
    $name === '' || mb_strlen($name) > 160 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    json_response(422, ['error' => 'Revisa el tipo de solicitud, tus datos de contacto, el asunto y el mensaje.']);
}

try {
    $query = db()->prepare(
        'INSERT INTO ext_ayuda_sugerencias
            (id_usuario, tipo, nombre_contacto, correo_contacto, asunto, mensaje)
         VALUES (:id_usuario, :tipo, :nombre, :correo, :asunto, :mensaje)'
    );
    $query->execute([
        'id_usuario' => $user['id'] ?? null,
        'tipo' => $type,
        'nombre' => $name,
        'correo' => $email,
        'asunto' => $subject,
        'mensaje' => $message,
    ]);
    json_response(201, ['message' => 'Tu solicitud fue enviada correctamente. Gracias por ayudarnos a mejorar InvestigaTec.']);
} catch (Throwable $error) {
    error_log('InvestigaTec registrar solicitud de ayuda: ' . $error->getMessage());
    json_response(500, ['error' => 'No se pudo registrar tu solicitud. Inténtalo más tarde.']);
}
