<?php $usuarioEncabezado = current_user(); ?>
<header class="topbar"><button id="menu-toggle" aria-label="Mostrar menú" aria-expanded="false" aria-controls="sidebar" class="mobile-menu">☰</button><div class="top-title">Instituto Tecnológico de Ciudad Juárez</div><div class="top-right">
  <?php if ($usuarioEncabezado && empty($usuarioEncabezado['is_admin'])): ?>
    <a class="notifications-shortcut" href="?page=notificaciones" aria-label="Notificaciones">
      <span>Notificaciones</span>
      <span class="notifications-badge" id="unread-notifications-count" hidden>0</span>
    </a>
  <?php endif; ?>
  <div class="account-menu">
    <button class="account-link" type="button" id="account-toggle" aria-expanded="false" aria-controls="account-popover">
      <?php if (!empty($usuarioEncabezado['portrait'])): ?>
        <img class="account-trigger-avatar" src="<?= e($usuarioEncabezado['portrait']) ?>" alt="">
      <?php else: ?>
        <span class="account-trigger-avatar account-trigger-placeholder" aria-hidden="true">👤</span>
      <?php endif; ?>
      <span><?= e($usuarioEncabezado['name'] ?? 'Mi cuenta') ?></span>
    </button>
    <section class="account-popover" id="account-popover" aria-labelledby="account-toggle" hidden>
      <?php if ($usuarioEncabezado): ?>
        <div class="account-popover-user">
          <?php if (!empty($usuarioEncabezado['portrait'])): ?>
            <img class="account-popover-avatar" src="<?= e($usuarioEncabezado['portrait']) ?>" alt="Foto de perfil">
          <?php else: ?>
            <span class="account-popover-avatar account-trigger-placeholder" aria-hidden="true">👤</span>
          <?php endif; ?>
          <div><strong><?= e($usuarioEncabezado['name']) ?></strong><span><?= e($usuarioEncabezado['email']) ?></span></div>
        </div>
        <?php if ($usuarioEncabezado['type'] === 'ESTUDIANTE' && empty($usuarioEncabezado['is_admin'])): ?><a class="account-popover-action" href="?page=perfil">Editar perfil</a><?php endif; ?>
        <button class="account-popover-action" id="logout" type="button">Cerrar sesión</button>
        <p id="logout-error" role="alert" aria-live="polite"></p>
      <?php else: ?>
        <p>Inicia sesión para acceder a tu cuenta.</p>
        <a class="button primary" href="?page=login">Iniciar sesión</a>
        <a class="button secondary" href="?page=registro">Registrarse</a>
      <?php endif; ?>
    </section>
  </div>
</div></header>