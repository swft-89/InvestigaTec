<?php $usuarioAyuda = current_user(); ?>
<div class="page-head">
  <p class="eyebrow">Estamos para ayudarte</p>
  <h1>Ayuda y soporte</h1>
  <p class="subtitle">Encuentra respuestas rápidas o envíanos una consulta, sugerencia o reporte.</p>
</div>

<div class="help-layout">
  <section class="panel help-faq" aria-labelledby="faq-heading">
    <h2 id="faq-heading">Preguntas frecuentes</h2>
    <details>
      <summary>¿Cómo registro un proyecto de investigación?</summary>
      <p>Inicia sesión y ve a <a href="?page=subir">Subir artículo</a>. Completa los datos del proyecto, agrega sus autores y adjunta el documento en PDF.</p>
    </details>
    <details>
      <summary>¿Quién puede consultar o descargar un proyecto?</summary>
      <p>Los proyectos publicados aparecen en Explorar. Para descargar el archivo PDF es necesario iniciar sesión.</p>
    </details>
    <details>
      <summary>¿Cómo reviso el estado de mi proyecto?</summary>
      <p>Consulta <a href="?page=mis-articulos">Mis artículos</a>. También recibirás una notificación cuando cambie el estado de un proyecto en el que participas como autor.</p>
    </details>
    <details>
      <summary>¿Qué hago si tengo un problema con mi cuenta?</summary>
      <p>Describe el problema en el formulario de esta página. Si tienes una cuenta, inicia sesión para que podamos asociar tu solicitud.</p>
    </details>
    <p class="help-note">Las solicitudes quedan registradas para que el equipo de InvestigaTec pueda darles seguimiento.</p>
  </section>

  <form class="panel form-panel help-form" id="help-form">
    <h2>Envíanos un mensaje</h2>
    <p class="muted">Los campos marcados con * son obligatorios.</p>
    <?php if ($usuarioAyuda): ?>
      <p class="help-account">Solicitud asociada a <strong><?= e($usuarioAyuda['name']) ?></strong> (<?= e($usuarioAyuda['email']) ?>).</p>
    <?php else: ?>
      <p class="help-account">Puedes enviar una solicitud sin iniciar sesión. Déjanos tus datos para poder responderte.</p>
    <?php endif; ?>
    <div class="form-grid">
      <?php if (!$usuarioAyuda): ?>
        <label>Nombre *
          <input name="nombre" type="text" maxlength="160" autocomplete="name" required>
        </label>
        <label>Correo electrónico *
          <input name="correo" type="email" maxlength="255" autocomplete="email" required>
        </label>
      <?php endif; ?>
      <label>Tipo de solicitud *
        <select name="tipo" required>
          <option value="">Selecciona una opción</option>
          <option value="AYUDA">Necesito ayuda</option>
          <option value="SUGERENCIA">Tengo una sugerencia</option>
          <option value="PROBLEMA">Quiero reportar un problema</option>
        </select>
      </label>
      <label>Asunto *
        <input name="asunto" type="text" minlength="3" maxlength="150" required>
      </label>
      <label class="wide">Mensaje *
        <textarea name="mensaje" rows="6" minlength="10" maxlength="5000" required placeholder="Cuéntanos cómo podemos ayudarte (máximo 5,000 caracteres)."></textarea>
      </label>
    </div>
    <div class="form-actions">
      <button class="button primary" type="submit">Enviar solicitud</button>
    </div>
    <p id="help-message" role="status" aria-live="polite"></p>
  </form>
</div>
