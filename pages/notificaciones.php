<div class="page-head"><p class="eyebrow">Actividad</p><h1>Notificaciones</h1><p class="subtitle">Revisa los avisos relacionados con tus artículos de investigación.</p></div>
<?php if (!current_user()): ?>
<section class="panel form-panel"><p>Inicia sesión para consultar tus notificaciones.</p><a class="button primary" href="?page=login">Iniciar sesión</a></section>
<?php else: ?>
<div class="panel">
  <div class="section-heading"><h2>Avisos</h2></div>
  <div class="notifications-toolbar">
    <label>
      Buscar notificaciones
      <input id="notification-search" type="search" maxlength="200" placeholder="Buscar por mensaje o artículo">
    </label>
    <label>
      Filtrar
      <select id="notification-filter">
        <option value="all">Todas</option>
        <option value="unread">Sin leer</option>
        <option value="read">Leídas</option>
      </select>
    </label>
    <div class="notification-limits" role="group" aria-label="Cantidad de notificaciones">
      <button class="button secondary" type="button" data-notification-limit="5" aria-pressed="true">5</button>
      <button class="button secondary" type="button" data-notification-limit="10" aria-pressed="false">10</button>
      <button class="button secondary" type="button" data-notification-limit="15" aria-pressed="false">15</button>
      <button class="button secondary" type="button" data-notification-limit="all" aria-pressed="false">Ver todas</button>
    </div>
  </div>
  <p id="notifications-message" role="status" aria-live="polite">Cargando notificaciones...</p>
  <div id="notifications-list"></div>
</div>
<?php endif; ?>