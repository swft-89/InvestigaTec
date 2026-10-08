<aside class="sidebar" id="sidebar"><a class="brand" href="index.php"><img src="assets/img/logo-investigatec.png" alt="InvestigaTec"><div class="brand-caption">Repositorio académico</div></a>
<p class="nav-caption">REPOSITORIO</p><nav class="menu-list">
<?php foreach (['inicio','explorar','mis-articulos','subir','resumenes'] as $p): ?><a href="?page=<?= $p ?>" class="<?= $page===$p?'active':'' ?>"><?= e($labels[$p]) ?></a><?php endforeach; ?></nav>
<p class="nav-caption">CUENTA</p><nav class="menu-list"><?php foreach (['perfil','notificaciones','acceso'] as $p): ?><a href="?page=<?= $p ?>" class="<?= $page===$p?'active':'' ?>"><?= e($labels[$p]) ?></a><?php endforeach; ?></nav>
<div class="sidebar-foot">Proyecto InvestigaTec<br><small>Versión de interfaz · 2026</small></div></aside>