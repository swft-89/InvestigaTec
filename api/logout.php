<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
post_only(); require_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>$p['samesite']]);
}
session_destroy();
json_response(200,['message'=>'Sesión cerrada.']);
