<div class="page-head"><p class="eyebrow">Actividad</p><h1>Notificaciones</h1><p class="subtitle">Revisa los avisos relacionados con tus artículos de investigación.</p></div>
<?php if (!current_user()): ?>
<section class="panel form-panel"><p>Inicia sesión para consultar tus notificaciones.</p><a class="button primary" href="?page=login">Iniciar sesión</a></section>
<?php else: ?>
<div class="panel"><div class="section-heading"><h2>Avisos recientes</h2><button id="mark-read" class="button secondary" type="button">Marcar como leídos</button></div><p id="notifications-message" role="status" aria-live="polite">Cargando notificaciones...</p><div id="notifications-list"></div></div>
<?php endif; ?>