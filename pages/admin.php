<div class="page-head">
  <p class="eyebrow">Acceso restringido · Administración</p>
  <h1>Panel administrativo</h1>
  <p class="subtitle">Gestiona cuentas, revisa artículos reportados y responde solicitudes de soporte.</p>
</div>
<p id="admin-message" role="status" aria-live="polite">Cargando información administrativa...</p>
<div class="admin-tabs" role="tablist" aria-label="Secciones administrativas">
  <button type="button" class="admin-tab active" role="tab" aria-selected="true" aria-controls="admin-users" data-admin-tab="users">Usuarios</button>
  <button type="button" class="admin-tab" role="tab" aria-selected="false" aria-controls="admin-projects" data-admin-tab="projects">Artículos</button>
  <button type="button" class="admin-tab" role="tab" aria-selected="false" aria-controls="admin-reports" data-admin-tab="reports">Reportes</button>
  <button type="button" class="admin-tab" role="tab" aria-selected="false" aria-controls="admin-support" data-admin-tab="support">Ayuda y sugerencias</button>
</div>
<section id="admin-users" class="panel admin-section" role="tabpanel">
  <div class="section-heading">
    <h2>Usuarios</h2>
    <div class="admin-list-controls"><span id="admin-user-count" class="muted"></span>
      <label>Mostrar <select data-admin-limit="users" aria-label="Cantidad de usuarios"><option value="5">5</option><option value="10">10</option><option value="15">15</option><option value="all">Todos</option></select></label>
    </div>
  </div>
  <div class="table-scroller"><table class="admin-table">
    <thead><tr><th>Usuario</th><th>Correo</th><th>Tipo</th><th>Afiliación</th><th>Estado</th><th>Acción</th></tr></thead>
    <tbody id="admin-users-list"></tbody>
  </table></div>
</section>
<section id="admin-projects" class="panel admin-section" role="tabpanel" hidden>
  <div class="section-heading">
    <h2>Artículos</h2>
    <div class="admin-list-controls"><span id="admin-project-count" class="muted"></span>
      <label>Mostrar <select data-admin-limit="projects" aria-label="Cantidad de artículos"><option value="5">5</option><option value="10">10</option><option value="15">15</option><option value="all">Todos</option></select></label>
    </div>
  </div>
  <div class="table-scroller"><table class="admin-table">
    <thead><tr><th>Artículo</th><th>Autor de registro</th><th>Estado actual</th><th>Reportes abiertos</th><th>Nuevo estado</th></tr></thead>
    <tbody id="admin-projects-list"></tbody>
  </table></div>
</section>
<section id="admin-reports" class="panel admin-section" role="tabpanel" hidden>
  <div class="section-heading">
    <h2>Reportes de artículos</h2>
    <div class="admin-list-controls"><span id="admin-report-count" class="muted"></span>
      <label>Mostrar <select data-admin-limit="reports" aria-label="Cantidad de reportes"><option value="5">5</option><option value="10">10</option><option value="15">15</option><option value="all">Todos</option></select></label>
    </div>
  </div>
  <div id="admin-reports-list"></div>
</section>
<section id="admin-support" class="panel admin-section" role="tabpanel" hidden>
  <div class="section-heading">
    <h2>Solicitudes de ayuda y sugerencias</h2>
    <div class="admin-list-controls"><span id="admin-request-count" class="muted"></span>
      <label>Mostrar <select data-admin-limit="requests" aria-label="Cantidad de solicitudes"><option value="5">5</option><option value="10">10</option><option value="15">15</option><option value="all">Todas</option></select></label>
    </div>
  </div>
  <div id="admin-support-list"></div>
</section>
<dialog id="admin-support-dialog" class="admin-support-dialog" aria-labelledby="admin-support-dialog-title">
  <div class="section-heading">
    <h2 id="admin-support-dialog-title"></h2>
    <button id="admin-support-dialog-close" class="button secondary" type="button">Cerrar</button>
  </div>
  <p id="admin-support-dialog-meta" class="muted"></p>
  <div id="admin-support-dialog-message" class="admin-message-body"></div>
  <div id="admin-support-dialog-previous-reply" class="admin-reply" hidden></div>
  <form id="admin-support-reply" class="admin-reply-form">
    <label>Respuesta al usuario
      <textarea name="reply" rows="5" maxlength="5000" required placeholder="Escribe la respuesta para el usuario..."></textarea>
    </label>
    <button class="button primary" type="submit">Enviar respuesta</button>
  </form>
</dialog>
<?php require __DIR__ . '/../includes/user-profile-dialog.php'; ?>
