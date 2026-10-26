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
  <div class="section-heading"><h2>Usuarios</h2><span id="admin-user-count" class="muted"></span></div>
  <div class="table-scroller"><table class="admin-table">
    <thead><tr><th>Usuario</th><th>Correo</th><th>Tipo</th><th>Afiliación</th><th>Estado</th><th>Acción</th></tr></thead>
    <tbody id="admin-users-list"></tbody>
  </table></div>
</section>
<section id="admin-projects" class="panel admin-section" role="tabpanel" hidden>
  <div class="section-heading"><h2>Artículos</h2><span id="admin-project-count" class="muted"></span></div>
  <div class="table-scroller"><table class="admin-table">
    <thead><tr><th>Artículo</th><th>Autor de registro</th><th>Estado actual</th><th>Reportes abiertos</th><th>Nuevo estado</th></tr></thead>
    <tbody id="admin-projects-list"></tbody>
  </table></div>
</section>
<section id="admin-reports" class="panel admin-section" role="tabpanel" hidden>
  <div class="section-heading"><h2>Reportes de artículos</h2><span id="admin-report-count" class="muted"></span></div>
  <div id="admin-reports-list"></div>
</section>
<section id="admin-support" class="panel admin-section" role="tabpanel" hidden>
  <div class="section-heading"><h2>Solicitudes de ayuda y sugerencias</h2><span id="admin-request-count" class="muted"></span></div>
  <div id="admin-support-list"></div>
</section>
