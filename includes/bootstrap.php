<?php
require_once __DIR__.'/auth.php';
$config = require __DIR__.'/../config/app.php';
$allowed = ['inicio','explorar','mis-articulos','articulo','detalle-articulo','subir','resumenes','perfil','notificaciones','ayuda','admin','revision','login','registro'];
$page = $_GET['page'] ?? 'inicio';
if (!in_array($page,$allowed,true)) {$page='inicio';}
if ($page === 'admin' && empty(current_user()['is_admin'])) {
    http_response_code(403);
    $page = 'inicio';
}
if ($page === 'revision' && !in_array(current_user()['type'] ?? '', ['DOCENTE', 'ADMINISTRADOR'], true) && empty(current_user()['is_admin'])) {
    http_response_code(403);
    $page = 'inicio';
}
function e(string|int|float|bool|null $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$labels=['inicio'=>'Inicio','explorar'=>'Explorar artículos','mis-articulos'=>'Mis artículos','articulo'=>'Artículo','detalle-articulo'=>'Artículo publicado','subir'=>'Subir artículo','resumenes'=>'Resúmenes','perfil'=>'Mi perfil','notificaciones'=>'Notificaciones','ayuda'=>'Ayuda y soporte','admin'=>'Administración','revision'=>'Revisión académica','login'=>'Iniciar sesión','registro'=>'Registro'];
