<?php
$config = require __DIR__.'/../config/app.php';
$allowed = ['inicio','explorar','mis-articulos','subir','resumenes','perfil','notificaciones','acceso'];
$page = $_GET['page'] ?? 'inicio';
if (!in_array($page,$allowed,true)) {$page='inicio';}
function e($text){return htmlspecialchars((string)$text,ENT_QUOTES,'UTF-8');}
$labels=['inicio'=>'Inicio','explorar'=>'Explorar artículos','mis-articulos'=>'Mis artículos','subir'=>'Subir artículo','resumenes'=>'Resúmenes','perfil'=>'Mi perfil','notificaciones'=>'Notificaciones','acceso'=>'Mi cuenta'];
