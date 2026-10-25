<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

function require_admin(PDO $pdo): array
{
    $user = current_user();
    if (!$user) json_response(401, ['error' => 'Inicia sesión con la cuenta administradora.']);

    $query = $pdo->prepare(
        "SELECT id_usuario FROM usuarios
         WHERE id_usuario = :id
           AND LOWER(correo) = 'admin@investigatec.mx'
           AND ext_administrador IS TRUE
           AND ext_aprobada IS TRUE
           AND estado_cuenta = 'ACTIVA'"
    );
    $query->execute(['id' => $user['id']]);
    if (!$query->fetchColumn()) json_response(403, ['error' => 'No tienes permisos de administrador.']);
    return $user;
}
