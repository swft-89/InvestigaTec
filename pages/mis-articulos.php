<div class="page-head"><p class="eyebrow">Espacio del estudiante</p><h1>Mis artículos</h1><p class="subtitle">Consulta las investigaciones registradas por ti o en las que participas como autor.</p></div>
<?php if (!current_user()): ?>
  <section class="panel form-panel">
    <p>Inicia sesión para consultar tus investigaciones.</p>
    <a class="button primary" href="?page=login">Iniciar sesión</a>
  </section>
<?php else: ?>
  <div class="section-heading"><h2>Artículos registrados</h2><a class="button primary" href="?page=subir">Nuevo artículo</a></div>
  <div class="panel table-scroller">
    <table>
      <thead><tr><th>Investigación</th><th>Área</th><th>Fecha</th><th>Estado</th><th>Versión</th><th>Documento</th></tr></thead>
      <tbody id="my-list"><tr><td colspan="6">Cargando investigaciones...</td></tr></tbody>
    </table>
  </div>
<?php endif; ?>