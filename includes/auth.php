<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        session_start();
    }
}
function csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_csrf(): void {
    $incoming = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($incoming) || !hash_equals(csrf_token(), $incoming)) {
        json_response(419, ['error' => 'La sesion vencio. Recarga la pagina e intentalo nuevamente.']);
    }
}
function json_response(int $status, array $data): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function json_input(): array {
    if (($_SERVER['CONTENT_TYPE'] ?? '') !== 'application/json' && !str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json;')) {
        json_response(415,['error'=>'El cuerpo debe ser JSON.']);
    }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) json_response(413,['error'=>'Solicitud demasiado grande.']);
    $input = json_decode(file_get_contents('php://input', false, null, 0, 16385), true);
    if (!is_array($input)) json_response(400,['error'=>'JSON invalido.']);
    return $input;
}
function post_only(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        json_response(405,['error'=>'Metodo no permitido.']);
    }
}
function login_user(array $u): void {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['user'] = [
        'id' => (int)$u['id_usuario'],
        'name' => trim($u['nombre'].' '.$u['apellido_paterno'].' '.($u['apellido_materno'] ?? '')),
        'email' => $u['correo'],
        'type' => $u['tipo_usuario'],
    ];
}
