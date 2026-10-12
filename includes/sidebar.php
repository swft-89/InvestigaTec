<?php
  $defaultLabels = [
    'inicio' => 'Inicio',
    'explorar' => 'Explorar',
    'mis-articulos' => 'Mis artículos',
    'subir' => 'Subir',
    'resumenes' => 'Resúmenes',
    'perfil' => 'Perfil',
    'notificaciones' => 'Notificaciones',
  ];
  $labels = $labels ?? $defaultLabels;
?>

<aside class="sidebar" id="sidebar" aria-label="Navegación principal">
  <button class="sidebar-close" id="sidebar-close" type="button" aria-label="Cerrar menú">×</button>
  <a class="brand" href="index.php">
    <img src="assets/img/logo-investigatec.png" alt="InvestigaTec">
    <span class="brand-caption">Repositorio académico</span>
  </a>

  <p class="nav-caption" id="repository-nav-label">Repositorio</p>
  <nav class="menu-list" aria-labelledby="repository-nav-label">
    <?php foreach (['inicio', 'explorar', 'mis-articulos', 'subir', 'resumenes'] as $p): ?>
      <a
        href="?page=<?= e($p) ?>"
        class="<?= isset($page) && $page === $p ? 'active' : '' ?>"
        <?= isset($page) && $page === $p ? 'aria-current="page"' : '' ?>
      ><?= e($labels[$p] ?? $defaultLabels[$p] ?? ucfirst(str_replace('-', ' ', $p))) ?></a>
    <?php endforeach; ?>
  </nav>

  <p class="nav-caption" id="account-nav-label">Cuenta</p>
  <nav class="menu-list" aria-labelledby="account-nav-label">
    <?php foreach (['perfil', 'notificaciones'] as $p): ?>
      <a
        href="?page=<?= e($p) ?>"
        class="<?= isset($page) && $page === $p ? 'active' : '' ?>"
        <?= isset($page) && $page === $p ? 'aria-current="page"' : '' ?>
      ><?= e($labels[$p] ?? $defaultLabels[$p] ?? ucfirst(str_replace('-', ' ', $p))) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-foot">
    Proyecto InvestigaTec<br>
    <small>Versión de interfaz · 2026</small>
  </div>
</aside>
<button class="sidebar-backdrop" id="sidebar-backdrop" type="button" aria-label="Cerrar menú" tabindex="-1" hidden></button>
