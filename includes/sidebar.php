<?php
  $defaultLabels = [
    'inicio' => 'Inicio',
    'explorar' => 'Explorar',
    'mis-articulos' => 'Mis artículos',
    'subir' => 'Subir',
    'perfil' => 'Perfil',
    'notificaciones' => 'Notificaciones',
    'ayuda' => 'Ayuda y soporte',
  ];
  $labels = $labels ?? $defaultLabels;
  $usuarioSidebar = current_user();
  $esAdministrador = !empty($usuarioSidebar['is_admin']);
?>

<aside class="sidebar" id="sidebar" aria-label="Navegación principal">
  <button class="sidebar-close" id="sidebar-close" type="button" aria-label="Cerrar menú">×</button>
  <a class="brand" href="<?= $esAdministrador ? '?page=admin' : 'index.php' ?>">
    <img src="assets/img/logo-investigatec.png" alt="InvestigaTec">
    <span class="brand-caption">Repositorio académico</span>
  </a>

  <?php if ($esAdministrador): ?>
    <p class="nav-caption" id="admin-nav-label">Administración</p>
    <nav class="menu-list" aria-labelledby="admin-nav-label">
      <?php foreach (['admin' => 'Panel administrativo', 'revision' => 'Revisar artículos'] as $p => $label): ?>
        <a href="?page=<?= e($p) ?>"
          class="<?= isset($page) && $page === $p ? 'active' : '' ?>"
          <?= isset($page) && $page === $p ? 'aria-current="page"' : '' ?>
        ><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php else: ?>
    <p class="nav-caption" id="repository-nav-label">Repositorio</p>
    <nav class="menu-list" aria-labelledby="repository-nav-label">
      <?php foreach (['inicio', 'explorar', 'mis-articulos', 'subir'] as $p): ?>
        <a
          href="?page=<?= e($p) ?>"
          class="<?= isset($page) && $page === $p ? 'active' : '' ?>"
          <?= isset($page) && $page === $p ? 'aria-current="page"' : '' ?>
        ><?= e($labels[$p] ?? $defaultLabels[$p] ?? ucfirst(str_replace('-', ' ', $p))) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <?php if (!$esAdministrador && ($usuarioSidebar['type'] ?? '') === 'DOCENTE'): ?>
    <p class="nav-caption" id="review-nav-label">Revisión académica</p>
    <nav class="menu-list" aria-labelledby="review-nav-label">
      <a href="?page=revision" class="<?= isset($page) && $page === 'revision' ? 'active' : '' ?>" <?= isset($page) && $page === 'revision' ? 'aria-current="page"' : '' ?>>Revisar artículos</a>
    </nav>
  <?php endif; ?>

  <?php if (!$esAdministrador): ?>
    <p class="nav-caption" id="account-nav-label">Cuenta</p>
    <nav class="menu-list" aria-labelledby="account-nav-label">
      <?php foreach (['perfil', 'notificaciones', 'ayuda'] as $p): ?>
        <a
          href="?page=<?= e($p) ?>"
          class="<?= isset($page) && $page === $p ? 'active' : '' ?>"
          <?= isset($page) && $page === $p ? 'aria-current="page"' : '' ?>
        ><?= e($labels[$p] ?? $defaultLabels[$p] ?? ucfirst(str_replace('-', ' ', $p))) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <div class="sidebar-foot">
    <?php if ($esAdministrador): ?>
      <span class="admin-shortcut">Cuenta administradora</span><br>
    <?php endif; ?>
    Proyecto InvestigaTec<br>
  </div>
</aside>
<button class="sidebar-backdrop" id="sidebar-backdrop" type="button" aria-label="Cerrar menú" tabindex="-1" hidden></button>
