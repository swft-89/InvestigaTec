<div class="page-head">
  <p class="eyebrow">Comunidad académica</p>
  <h1>Buscar usuarios</h1>
  <p class="subtitle">Encuentra perfiles y consulta los artículos en los que participa cada persona.</p>
</div>
<?php if (!current_user()): ?>
  <section class="panel form-panel">
    <p>Inicia sesión para buscar usuarios y consultar sus perfiles.</p>
    <a class="button primary" href="?page=login">Iniciar sesión</a>
  </section>
<?php else: ?>
  <section class="panel user-directory">
    <label class="user-directory-search">
      Buscar por nombre, afiliación o tipo de usuario
      <input id="user-search" type="search" maxlength="100" placeholder="Ej. Ana Pérez, Ingeniería o DOCENTE">
    </label>
    <p id="user-directory-message" role="status" aria-live="polite">Escribe para buscar usuarios.</p>
    <div id="user-directory-results" class="user-directory-results"></div>
  </section>
  <?php require __DIR__ . '/../includes/user-profile-dialog.php'; ?>
<?php endif; ?>
